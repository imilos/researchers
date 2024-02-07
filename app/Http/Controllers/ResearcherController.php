<?php

namespace App\Http\Controllers;

use App\Models\Researcher;
use Illuminate\Http\Request;
use Solarium;
use Symfony\Component\EventDispatcher\EventDispatcher;
use App\Mail\AdminEmail;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;

// Korisno: https://haait.net/how-to-use-swagger-in-laravel/
// Korisno: https://wpwebinfotech.com/blog/laravel-swagger-integration/

/**
 * @OA\Info(
 * title="Swagger with Laravel",
 * version="1.0.0",
 * )
 * @OA\SecurityScheme(
 * type="http",
 * securityScheme="bearerAuth",
 * scheme="bearer",
 * bearerFormat="JWT"
 * )
 */
class ResearcherController extends Controller
{
    private $adapter;
    private $eventDispatcher;
    private $config;

    public function __construct()
    {
        $this->adapter = new Solarium\Core\Client\Adapter\Curl();
        $this->eventDispatcher = new EventDispatcher();
        $this->config = array(
            'endpoint' => array(
                'localhost' => array(
                    'host' => env("SOLR_HOSTNAME"),
                    'port' => env("SOLR_PORT"),
                    'path' => '/',
                    'core' => 'authority'
                )
            )
        );
    }

    /**
     * @OA\Get(
     * path="/api/researchers",
     * summary="Vraca sve istrazivace",
     * @OA\Response(
     * response=200,
     * description="Istrazivaci",
     * ),
     * )
     */
    public function index()
    {
        $researchers = Researcher::all();
        return response()->json($researchers);
    }

    /**
     * @OA\Get(
     * path="/api/getresearcherbyorcid/{orcid}",
     * summary="Vraca istrazivaca po ORCID-u",

     * @OA\Parameter(
     *   parameter="orcid",
     *   name="orcid",
     *   description="ORCID broj",
     *   @OA\Schema(
     *     type="string"
     *   ),
     *   in="path",
     *   required=true
     * ),

     * @OA\Response(
     * response=200,
     * description="Istrazivac",
     * ),
     * )
     */
    public function getresearcherbyorcid($orcid)
    {
        $r = Researcher::where('orcid', $orcid)->firstOrFail();
        return response()->json($r);
    }

    /**
     * @OA\Get(
     * path="/api/getresearcher/{authority}",
     * summary="Vraca istrazivaca po authority-ju",

     * @OA\Parameter(
     *   parameter="authority",
     *   name="authority",
     *   description="authority",
     *   @OA\Schema(
     *     type="string"
     *   ),
     *   in="path",
     *   required=true
     * ),

     * @OA\Response(
     * response=200,
     * description="Istrazivac",
     * ),
     * )
     */
    public function getresearcher($authority)
    {
        $client = new Solarium\Client($this->adapter, $this->eventDispatcher, $this->config);
        $query = $client->createSelect();
        $query->setQuery("id: $authority");
        $query->setFields(array('id', 'orcid_id'));
        $resultset = $client->select($query);

        $orcid_id = null;

        // Ako dati authority ima ORCID, onda pretrazi u bazi
        if ($resultset->getNumFound() > 0) {
            foreach ($resultset as $document)
                $orcid_id = $document->orcid_id;
        }

        $r = Researcher::where('orcid', $orcid_id)->first();

        // Ako ima takvog u bazi, vrati podatke
        if ($r != null) {
            $res = array(
                'status' => true,
                'authority' => $authority,
                'name' => $r->name,
                'orcid' => $r->orcid,
                'ecris' => $r->ecris,
                'scopus' => $r->scopusid,
                'link' => $r->link
            );

            return response()->json($res);
        }
        // Ako ima samo ORCID iz Solr-a, vrati ga
        elseif ($orcid_id != null) {
            $res = array(
                'status' => true,
                'authority' => $authority,
                'orcid' => $orcid_id
            );

            return response()->json($res);
        }
        // Ako nema nicega, vrati false
        else {
            return response()->json([
                'status' => false,
                'authority' => $authority
            ]);
        }
    }

    /**
     * Vraca publikacije prema ORCID-u
     */
    public function publicationsbyorcid($orcid)
    {
        $FRONTEND_URL = env("FRONTEND_URL");

        $client = new Solarium\Client($this->adapter, $this->eventDispatcher, $this->config);
        $query = $client->createSelect();
        $query->setQuery("orcid_id: $orcid");
        $query->setFields(array('id', 'orcid_id', 'value'));
        $resultset = $client->select($query);

        $authority = null;

        if ($resultset->getNumFound() > 0) {
            foreach ($resultset as $document)
                $authority = $document->id;
            $name = $document->value;
        }

        if ($authority != null)
            return redirect("$FRONTEND_URL/browse/author?value=$name&authority=$authority");
        else
            return redirect("$FRONTEND_URL/browse/author");
    }


    /**
     * @OA\Post(
     * path="/api/reporterrorinitem",
     * summary="Salje mejl sa primedbom",

     * @OA\RequestBody(
     *  @OA\JsonContent(
     *      @OA\Property(
     *           property="name",
     *           description="Ime",
     *           type="string",
     *           nullable="false",
     *           example="Milos Ivanovic"
     *      ),
     *      @OA\Property(
     *           property="email",
     *           description="email",
     *           type="string",
     *           nullable="false",
     *           example="milos.ivanovic@pmf.kg.ac.rs"
     *      ), 
     *      @OA\Property(
     *           property="uri",
     *           description="URI handle publikacije",
     *           type="string",
     *           nullable="false",
     *           example="http://rifoc.ikbks.com/items/7a4b9454-23a3-4169-8c52-029746d5201c"
     *      ), 
     *      @OA\Property(
     *           property="title",
     *           description="Naziv publikacije",
     *           type="string",
     *           nullable="false",
     *           example="Effect of Nitrogen Fertiliser and Lime on the Floristic Composition..."
     *      ), 
     *      @OA\Property(
     *           property="note",
     *           description="Poruka administratoru",
     *           type="string",
     *           nullable="false",
     *           example="Treba dodati apstrakt..."
     *      ), 
     *  )
     * ),
     * 
     * @OA\Response(
     * response=200,
     * description="",
     * ),
     * )
     */
    public function ReportErrorInItem(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required',
            'email' => 'required|email',
            'uri' => 'required',
            'title' => 'required',
            'note' => 'required'
        ]);

        // Vrati gresku ako neko polje nedostaje
        if ($validator->fails()) return response()->json($request, 422);

        // Validiran request
        $validated = $validator->validated();

        Mail::to(env("MAIL_SITE_ADMIN"))->send(new AdminEmail($validated));
        return response()->json($request);
    }
}
