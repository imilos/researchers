<?php

namespace App\Http\Controllers;

use App\Models\Researcher;
use Illuminate\Http\Request;
use Solarium;
use Symfony\Component\EventDispatcher\EventDispatcher;
use App\Mail\AdminEmail;
use Illuminate\Support\Facades\Mail;

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

    public function index()
    {
        $researchers = Researcher::all();
        return response()->json($researchers);
    }

    /**
     * Vraca istrazivaca po ORCID-u
     */
    public function getresearcherbyorcid($orcid)
    {
        $r = Researcher::where('orcid', $orcid)->firstOrFail();
        return response()->json($r);
    }

    /**
     * Vraca istraizivaca po authority-ju
     */
    public function getresearcher($authority)
    {
        $client = new Solarium\Client($this->adapter, $this->eventDispatcher, $this->config);
        $query = $client->createSelect();
        $query->setQuery("id: $authority");
        $query->setFields(array('id','orcid_id'));
        $resultset = $client->select($query);

        $orcid_id = null;

        // Ako dati authority ima ORCID, onda pretrazi u bazi
        if ($resultset->getNumFound() > 0)
        {
            foreach ($resultset as $document)
                $orcid_id = $document->orcid_id;
        }
        
        $r = Researcher::where('orcid', $orcid_id)->first();
        
        // Ako ima takvog, vrati podatke
        if ($r != null) {
            $res = array(
                'status' => true,
                'authority' => $authority,
                'name' => $r->name, 
                'orcid' => $r->orcid,
                'ecris' => $r->ecris,
                'scopus' => $r->scopusid
            );
            
            return response()->json($res);
        }
        // ako nema, vrati samo authority
        else {
            return response()->json([
                'status' => false, 
                'authority' => $authority
            ]);
        }
    }

    /**
     * Salje mail adminu sa odgovarajucim izvestajem,
     * (name, email, uri, title, note)
     */
    public function ReportErrorInItem(Request $request)
    {
        Mail::to(env("MAIL_SITE_ADMIN"))->send(new AdminEmail($request));
        return response()->json($request);
    }
}
