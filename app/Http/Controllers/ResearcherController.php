<?php

namespace App\Http\Controllers;

use App\Models\Researcher;
use Illuminate\Http\Request;
use Solarium;
use Symfony\Component\EventDispatcher\EventDispatcher;
use App\Mail\AdminEmail;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;

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
     * Vraca publikacije prema ORCID-u
     */
    public function publicationsbyorcid($orcid)
    {
        $FRONTEND_URL = env("FRONTEND_URL");
        
        $client = new Solarium\Client($this->adapter, $this->eventDispatcher, $this->config);
        $query = $client->createSelect();
        $query->setQuery("orcid_id: $orcid");
        $query->setFields(array('id','orcid_id','value'));
        $resultset = $client->select($query);

        $authority = null;

        if ($resultset->getNumFound() > 0)
        {
            foreach ($resultset as $document)
                $authority = $document->id;
                $name = $document->value;
        }

        if ($authority!=null)
            return redirect("$FRONTEND_URL/browse/author?value=$name&authority=$authority");
        else
            return redirect("$FRONTEND_URL/browse/author");
    }


    /**
     * Salje mail adminu sa odgovarajucim izvestajem
     * Mora da sadrzi {name, email, uri, title, note}
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
