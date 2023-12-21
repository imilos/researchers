# Researchers
Jednostavni API koji služi kao dodatak DSpace 7.x. API. Obezbeđuje:

* SQLite bazu sa identifikatorima istraživača. Neophodna je tabela **reseearchers** oblika `(id, orcid, ecris, scopusid, name, email, faculty, department, search_index)`
* vezu sa SOLR indeksom,
* pretragu istraživača po ORCID-u,
* pretragu istraživača po DSpace authority-ju,
* slanje mejla adminu repozitorija sa detaljima primedbe.

## Konfiguracija
U `.env` fajlu važno je definisati sledeće varijable:

    DB_CONNECTION=sqlite
    DB_DATABASE=/home/milos/researchers/database/database.sqlite
    DB_FOREIGN_KEYS=true
    
    SOLR_HOSTNAME=
    SOLR_PORT=8983
    FRONTEND_URL=https://dspace.unic.kg.ac.rs
    
    MAIL_MAILER=smtp
    MAIL_HOST=
    MAIL_PORT=
    MAIL_USERNAME=
    MAIL_PASSWORD=
    MAIL_ENCRYPTION=
    MAIL_FROM_ADDRESS=
    MAIL_FROM_NAME="${APP_NAME}"
    MAIL_SITE_ADMIN=

## API pozivi
Rute koje ovo omogućavaju su sledeće:

### GET pozivi
* `/getresearcherbyorcid/{orcid}` - vraća podatke o istraživaču sa datim ORCID-om,
* `/publicationsbyorcid/{orcid}` - redirektuje na stranicu koja izlistava publikacije traženog istraživača,
* `/getresearcher/{authority}` - vraća podatke o istraživaču sa datim authority-jem.

### POST pozivi
* /reporterrorinitem - Zahteva zahtev oblika npr. `{"name":"Milos Ivanovic","email":"mivanovic@kg.ac.rs","title":"Neka publikacija","uri":"https://scidar.kg.ac.rs/handle/123456789/19735","note":"Pogresan prvi autor"}`
