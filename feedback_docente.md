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

# Feedback Docente – Gantt e Project Plan

Il materiale consegnato mostra un miglioramento rispetto alla revisione precedente. In particolare, il Project Plan risulta ora distinto dal diagramma di Gantt e include elementi corretti di organizzazione del progetto, come obiettivi, assegnazione dei ruoli, pianificazione delle attività e attività di test previste.

Il diagramma di Gantt risulta complessivamente ben strutturato e maggiormente dettagliato rispetto alla versione precedente, con una suddivisione più chiara delle attività e l’indicazione dei membri coinvolti nelle varie fasi del progetto.

Restano tuttavia alcune carenze da correggere nelle prossime milestone.

## Project Plan

Il documento descrive correttamente le attività previste, ma manca ancora una parte più orientata alla gestione del progetto. In particolare sarebbe necessario esplicitare:

- i criteri di completamento/verifica delle milestone;
- le dipendenze tra le attività principali (es. componenti frontend dipendenti dall’integrazione backend);
- i principali rischi progettuali e le possibili strategie di mitigazione;
- le modalità di gestione delle modifiche durante lo sviluppo;
- una descrizione più chiara del flusso organizzativo del team e dell’integrazione del lavoro tra i membri.

La sezione “Eventuali modifiche” risulta al momento troppo generica e dovrebbe descrivere in modo più concreto come il gruppo intende gestire variazioni di tempi, attività o responsabilità.

## Diagramma di Gantt

Il Gantt risulta corretto nell’impostazione generale, ma può essere ulteriormente migliorato:

- introducendo dipendenze esplicite tra task;
- suddividendo alcune attività molto ampie in sotto-task più granulari;
- evidenziando meglio le milestone principali del progetto;
- collegando in modo più chiaro le attività di test e integrazione alle fasi precedenti di sviluppo.

Nel complesso il lavoro mostra un recepimento concreto dei feedback precedenti e una migliore comprensione della pianificazione del progetto, anche se la parte di project management necessita ancora di maggiore approfondimento metodologico.
