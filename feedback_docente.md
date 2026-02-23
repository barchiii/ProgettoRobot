# Feedback Docente – Robot Frontend

## Analisi dei Requisiti

Nell’Analisi dei Requisiti è necessario esplicitare in modo univoco lo scopo del sistema frontend, chiarendo:

* quali obiettivi il sistema frontend deve supportare;
* quali componenti sono esterni al sistema (backend, robot/hardware, servizi terzi);
* i confini del progetto, per evitare ambiguità sull’ambito di responsabilità del frontend.

Nell’Analisi dei Requisiti è necessario identificare in modo esplicito gli attori che utilizzano il sistema, specificando:

* quali tipologie di utenti accedono all’interfaccia;
* se sono previsti ruoli diversi con permessi differenti;
* quali attori o sistemi non interagiscono direttamente con il frontend.

Nell’Analisi dei Requisiti è necessario elencare in modo chiaro le principali funzionalità richieste al frontend, specificando:

* quali pagine o schermate devono essere presenti e con quali contenuti minimi;
* quali azioni l’utente può compiere in ciascuna schermata;
* quali vincoli e validazioni devono essere applicati lato interfaccia;
* quali funzionalità sono obbligatorie e quali opzionali.

## Analisi Funzionale

Nell’Analisi Funzionale è necessario descrivere il comportamento del frontend in modo strutturato e coerente, mantenendo una chiara separazione dai dettagli tecnici.

In particolare, l’Analisi Funzionale deve includere:

* i principali flussi di navigazione del frontend, descrivendo la sequenza delle schermate e le condizioni di accesso;
* le azioni che l’utente può compiere all’interno dell’interfaccia, organizzate in casi d’uso chiari e comprensibili;
* gli stati dell’interfaccia (ad esempio caricamento, errore, assenza di dati) e il comportamento del sistema in presenza di situazioni eccezionali;
* la coerenza e la tracciabilità tra funzionalità descritte e requisiti definiti, evitando l’introduzione di funzionalità non giustificate.

## Analisi Tecnica (valutazione indicativa)

Nell’Analisi Tecnica è necessario descrivere come verrà realizzato il frontend, motivando le scelte progettuali e tecnologiche e indicando le principali componenti della soluzione.

In particolare, l’Analisi Tecnica dovrebbe includere:

* stack e strumenti adottati (framework/librerie, tooling, standard di progetto), motivandone la scelta;
* architettura del frontend (struttura delle pagine/componenti, routing, organizzazione del codice);
* schema logico dei dati (schema ER) dei principali oggetti gestiti dal frontend e delle relazioni rilevanti ai fini dell’interazione con il backend;
* gestione dello stato e dei dati (state management, caching, gestione sessione/autenticazione se prevista);
* comunicazione con backend o servizi esterni (API, gestione errori di rete, retry/timeouts, formati dati);
* gestione di sicurezza e qualità (validazioni lato client, gestione permessi lato UI, logging, test minimi);


