<?php

namespace App;

use Google_Service_Drive_DriveFile;
use GuzzleHttp\Psr7;
use League\Flysystem\Config;
use League\Flysystem\Util;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\StreamInterface;

use Masbug\Flysystem\GoogleDriveAdapter;
use Masbug\Flysystem\StreamableUpload;


/* Fix deprecated PSR7 method

$stream = Psr7\stream_for($contents); ----> Deprecated

$stream = Psr7\Utils::streamFor($contents);

*/


class GoogleDriveAdapterPSR7Fix extends GoogleDriveAdapter
{
    /**
     * Upload|Update item
     *
     * @param string          $path
     * @param string|resource $contents
     * @param Config          $config
     * @param bool|null       $updating If null then we check for existence of the file
     * @return array|false item info array
     */
    protected function upload($path, $contents, Config $config, $updating = null)
    {
        [$parentId, $fileName] = $this->splitPath($path);
        $mime = $config->get('mimetype');
        $file = new Google_Service_Drive_DriveFile();

        if ($updating === null || $updating === true) {
            $srcFile = $this->getFileObject($path);
            $updating = $srcFile !== null;
        } else {
            $srcFile = null;
        }
        if (!$updating) {
            $file->setName($fileName);
            $file->setParents([
                $parentId
            ]);
        }

        if (!$mime) {
            $mime = Util::guessMimeType($fileName, is_string($contents) ? $contents : '');
            if (empty($mime)) {
                $mime = 'application/octet-stream';
            }
        }
        $file->setMimeType($mime);

        /** @var StreamInterface $stream */
        $stream = Psr7\Utils::streamFor($contents);
        $size = $stream->getSize();

        if ($size <= self::MAX_CHUNK_SIZE) {
            // one shot upload
            $params = [
                'data' => $stream,
                'uploadType' => 'media',
                'fields' => self::FETCHFIELDS_GET
            ];

            if (!$updating) {
                $obj = $this->service->files->create($file, $this->applyDefaultParams($params, 'files.create'));
            } else {
                $obj = $this->service->files->update($srcFile->getId(), $file, $this->applyDefaultParams($params, 'files.update'));
            }
        } else {
            // chunked upload
            $client = $this->service->getClient();

            $params = [
                'fields' => self::FETCHFIELDS_GET
            ];

            $client->setDefer(true);
            if (!$updating) {
                /** @var RequestInterface $request */
                $request = $this->service->files->create($file, $this->applyDefaultParams($params, 'files.create'));
            } else {
                /** @var RequestInterface $request */
                $request = $this->service->files->update($srcFile->getId(), $file, $this->applyDefaultParams($params, 'files.update'));
            }

            $media = new StreamableUpload($client, $request, $mime, $stream, true, self::MAX_CHUNK_SIZE);
            $media->setFileSize($size);
            do {
                if (DEBUG_ME) {
                    echo "* Uploading next chunk.\n";
                }
                $status = $media->nextChunk();
            } while ($status === false);

            // The final value of $status will be the data from the API for the object that has been uploaded.
            if ($status !== false) {
                $obj = $status;
            }

            $client->setDefer(false);
        }

        $this->resetRequest($parentId);

        if (isset($obj) && $obj instanceof Google_Service_Drive_DriveFile) {
            $this->cacheFileObjects[$obj->getId()] = $obj;
            $this->cacheObjects([$obj->getId() => $obj]);
            $result = $this->normaliseObject($obj, self::dirname($path));

            if (($visibility = $config->get('visibility'))) {
                if ($this->setVisibility($result['virtual_path'], $visibility, true)) {
                    $result['visibility'] = $visibility;
                }
            }

            return $result;
        }
        return false;
    }
}