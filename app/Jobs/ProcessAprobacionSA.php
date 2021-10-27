<?php

namespace App\Jobs;

use App\Models\SociedadAnonima;
use App\MOdels\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Helpers\BonitaProcessHelper;
use App\Helpers\EstampilladoHelper;
use App\Helpers\URLHelper;
use App\Services\SociedadAnonimaService;
use Illuminate\Support\Facades\Http;
use PDF;
use Exception;

class ProcessAprobacionSA implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The sociedadAnonima instance.
     *
     * @var \App\Models\SociedadAnonima
     */
    protected $sociedadAnonima;

    /**
     * The user instance.
     *
     * @var \App\Models\User
     */
    protected $user;

    /**
     * The bonita case id
     *
     * @var string
     */
    protected $bonitaCaseId;

    /**
     * The new estado_evaluacion
     *
     * @var string
     */
    protected $nuevoEstadoEvaluacion;

    /**
     * Create a new job instance.
     *
     * @param  App\Models\SociedadAnonima  $sociedadAnonima
     * @return void
     */
    public function __construct(SociedadAnonima $sociedadAnonima, User $user, $bonitaCaseId, $nuevoEstadoEvaluacion)
    {
        $this->sociedadAnonima = $sociedadAnonima;
        $this->user = $user;
        $this->bonitaCaseId = $bonitaCaseId;
        $this->nuevoEstadoEvaluacion = $nuevoEstadoEvaluacion;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $bonitaProcessHelper = new BonitaProcessHelper();
        // Solicitar estampillado y setear numero_hash
        $estampilladoHelper = new EstampilladoHelper();
        $escribanoCredentials = [
            "email" => $this->user->email,
            "password" => 'grupo11'
        ];
        $loginResponse = $estampilladoHelper->login($escribanoCredentials);

        $service = new SociedadAnonimaService();

        $estampilladoResponse = $estampilladoHelper->solicitarEstampillado(
            $loginResponse["auth"]["access_token"],
            $service->getEstatutoContents($this->sociedadAnonima->nombre),
            $service->getEstatutoFileName($this->sociedadAnonima->nombre),
            $this->sociedadAnonima->numero_expediente
        );
        $numeroHash = $estampilladoResponse["numero_hash"];

        $image = str_replace('data:image/png;base64,', '', $estampilladoResponse["qr"]);
        $qr = str_replace(' ', '+', $image);
        $qr = base64_decode($qr);

        /* Guardar estatuto, pdf que contiene la información publica (Nombre, fecha de creación y socios) y el QR */
        $data = [
            "nombre" => $this->sociedadAnonima->nombre,
            "fechaCreacion" => $this->sociedadAnonima->fecha_creacion,
            "socios" => $this->sociedadAnonima->socios()->get(),
            "apoderado_id" => $this->sociedadAnonima->apoderado_id,
            "qr" => $estampilladoResponse["qr"],
        ];
        $pdf = PDF::loadView('pdf.infoPublicaSA', $data);

        // Store files
        $service->copyEstatutoToPublico($this->sociedadAnonima->nombre);
        $service->storePDF(
            $pdf->download()->getOriginalContent(),
            $this->sociedadAnonima->nombre
        );
        $service->storeQR(
            $qr,
            $this->sociedadAnonima->nombre
        );

        $urlHelper = new URLHelper();
        $apiLoginUrl = $urlHelper->getBonitaEndpointURL('/loginservice');

        $bonitaLoginResponse = Http::asForm()->post($apiLoginUrl, [
            'username' => config('services.bonita.admin_user'),
            'password' => config('services.bonita.admin_password'),
            'redirect' => 'false',
        ]);
        if ($bonitaLoginResponse->status() != 204)
            throw new Exception();

        $jsessionid = $bonitaLoginResponse->cookies()->toArray()[1]['Value'];
        $xBonitaAPIToken = $bonitaLoginResponse->cookies()->toArray()[2]['Value'];

        // numero_hash
        $this->sociedadAnonima->numero_hash = $numeroHash;
        $bonitaProcessHelper->updateCaseVariable($jsessionid, $xBonitaAPIToken, $this->bonitaCaseId, "numero_hash", "java.lang.String", $numeroHash);

        // Actualizar la SociedadAnonima
        $this->sociedadAnonima->estado_evaluacion = $this->nuevoEstadoEvaluacion;
        $this->sociedadAnonima->save();
    }
}