-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Creato il: Feb 25, 2026 alle 23:22
-- Versione del server: 10.4.32-MariaDB
-- Versione PHP: 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `netseadb`
--

-- --------------------------------------------------------

--
-- Struttura della tabella `donazione`
--

CREATE TABLE `donazione` (
  `id_donazione` int(11) NOT NULL,
  `importo` float DEFAULT NULL,
  `data` date DEFAULT NULL,
  `id_utente` int(11) DEFAULT NULL,
  `id_pd` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dump dei dati per la tabella `donazione`
--

INSERT INTO `donazione` (`id_donazione`, `importo`, `data`, `id_utente`, `id_pd`) VALUES
(1, 50, '2025-07-10', 2, 1),
(2, 120, '2025-08-22', 8, 1),
(3, 200, '2025-09-15', 7, 1),
(4, 30, '2025-10-03', 2, 4),
(5, 500, '2025-11-20', 8, 4),
(6, 75, '2025-12-01', 7, 5),
(7, 150, '2026-01-14', 2, 2),
(8, 100, '2026-01-28', 8, 6);

-- --------------------------------------------------------

--
-- Struttura della tabella `ente_di_ricerca`
--

CREATE TABLE `ente_di_ricerca` (
  `id_ente` int(11) NOT NULL,
  `nome` varchar(150) DEFAULT NULL,
  `tipo` varchar(50) DEFAULT NULL,
  `citta` varchar(100) DEFAULT NULL,
  `id_paese` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dump dei dati per la tabella `ente_di_ricerca`
--

INSERT INTO `ente_di_ricerca` (`id_ente`, `nome`, `tipo`, `citta`, `id_paese`) VALUES
(1, 'CNR-ISMAR (Istituto di Scienze Marine)', 'Istituto pubblico', 'Trieste', NULL),
(2, 'OGS (Ist. Naz. di Oceanografia e Geofisica Sperimentale)', 'Istituto pubblico', 'Trieste', NULL),
(3, 'ISPRA (Ist. Superiore Protezione e Ricerca Ambientale)', 'Istituto pubblico', 'Roma', NULL),
(4, 'Università di Bologna — Dip. Scienze Biologiche', 'Università', 'Bologna', NULL),
(5, 'Università Politecnica delle Marche', 'Università', 'Ancona', NULL);

-- --------------------------------------------------------

--
-- Struttura della tabella `habitat`
--

CREATE TABLE `habitat` (
  `id_habitat` int(11) NOT NULL,
  `nome` varchar(100) DEFAULT NULL,
  `descrizione` text DEFAULT NULL,
  `range_habitat` varchar(100) DEFAULT NULL,
  `temperatura` float DEFAULT NULL,
  `id_luogo` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struttura della tabella `like_media`
--

CREATE TABLE `like_media` (
  `id_post` int(11) NOT NULL,
  `id_utente` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dump dei dati per la tabella `like_media`
--

INSERT INTO `like_media` (`id_post`, `id_utente`) VALUES
(1, 2),
(1, 7),
(1, 8),
(2, 2),
(2, 8),
(3, 2),
(3, 7),
(3, 8),
(4, 7),
(4, 8),
(5, 2),
(5, 7),
(6, 2),
(6, 7),
(6, 8),
(7, 7),
(7, 8),
(8, 2),
(8, 7),
(8, 8),
(9, 8),
(10, 2),
(10, 7),
(11, 8),
(12, 2),
(12, 7),
(12, 8);

-- --------------------------------------------------------

--
-- Struttura della tabella `luogo`
--

CREATE TABLE `luogo` (
  `id_luogo` int(11) NOT NULL,
  `nome` varchar(100) DEFAULT NULL,
  `tipo` varchar(50) DEFAULT NULL,
  `oceano` varchar(100) DEFAULT NULL,
  `area` float DEFAULT NULL,
  `profondita` float DEFAULT NULL,
  `id_paese` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struttura della tabella `media`
--

CREATE TABLE `media` (
  `id_post` int(11) NOT NULL,
  `titolo` varchar(150) DEFAULT NULL,
  `descrizione` text DEFAULT NULL,
  `url` text DEFAULT NULL,
  `data_pub` date DEFAULT NULL,
  `visualizzazioni` int(11) DEFAULT NULL,
  `id_utente` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dump dei dati per la tabella `media`
--

INSERT INTO `media` (`id_post`, `titolo`, `descrizione`, `url`, `data_pub`, `visualizzazioni`, `id_utente`) VALUES
(1, 'Squalo bianco nell\'Arcipelago Toscano', 'Esemplare di Carcharodon carcharias stimato 4,5 m documentato a 18 m di profondità nell\'Arcipelago Toscano durante una campagna di monitoraggio CNR. L\'animale ha esplorato il ROV per circa 8 minuti. Notare il pattern di macchie sul fianco che permette la foto-identificazione individuale.', 'uploads/media/squalo_arcipelago.jpg', '2026-01-15', 1240, 1),
(2, 'Biocenosi corallina a 40 m — Baia di Capri', 'Transetto fotografico a 38–42 m di profondità nella Riserva Marina di Punta Campanella. La parete ospita gorgonie rosse (Paramuricea clavata), gorgonie gialle (Eunicella cavolini), spugne carnivore e stelle marine. Rilevato anche un esemplare adulto di cernia bruna di circa 40 kg.', 'uploads/media/corallo_capri.jpg', '2026-01-20', 843, 1),
(3, 'Pod di delfini in migrazione — Stretto di Messina', 'Documentazione del megapod di circa 200 Delphinus delphis osservato nello Stretto di Messina. Si vede il comportamento di herding cooperativo: sottogruppi comprimono il banco di acciughe da direzioni opposte prima di attaccare in sequenza. Vocalizzazioni registrate in contemporanea con idrofoni a 50 m.', 'uploads/media/delfini_messina.jpg', '2026-01-17', 3205, 1),
(4, 'Nidificazione Caretta caretta — Capo Rizzuto', 'Documentazione notturna della deposizione di una femmina di Caretta caretta identificata come CAL-2019-F07 sul litorale calabrese. Visibili le fasi di scavo del nido, deposizione (92 uova), ricopertura e rientro in mare. Operazione effettuata con luce rossa a bassa intensità.', 'uploads/media/tartaruga_nido.jpg', '2025-08-03', 2567, 5),
(5, 'Abisso Calypso: fondale a 2.800 m', 'Prime immagini dell\'AUV dell\'OGS a 2.800 m di profondità nell\'Abisso Calypso. Si riconoscono oloturie sul sedimento fangoso, bivalvi associati a emissioni idrotermali fredde, e tracce di brittle stars che si nutrono della neve marina proveniente dagli strati superiori.', 'uploads/media/abisso_calypso.jpg', '2025-10-17', 1891, 4),
(6, 'Polpo mimetizzato su fondale sabbioso — Isola d\'Elba', 'Un Octopus vulgaris modifica colore e texture della pelle in 0,4 secondi passando da colorazione uniforme beige (mimetica con la sabbia) a un pattern di macchie scure tipico della posidonia degradata. Notare l\'uso delle ventose come \"assaggio\" del substrato prima del cambio cromatico.', 'uploads/media/polpo_mimetismo.jpg', '2025-11-22', 4102, 1),
(7, 'Prateria di Posidonia — Isole Egadi', 'Documentazione dello stato di conservazione di una prateria di Posidonia oceanica nella Riserva Marina delle Isole Egadi. Matte compatte con foglie lunghe e sane, acqua cristallina con visibilità oltre 25 m, e ricca comunità ittica con salpe, castagnole e molti esemplari giovanili di specie commerciali.', 'uploads/media/posidonia_egadi.jpg', '2025-07-14', 2234, 6),
(8, 'Foca monaca a Lampedusa — tramonto', 'Avvistamento di un esemplare di Monachus monachus nei pressi della Spiaggia dei Conigli a Lampedusa, da una telecamera di sorveglianza dell\'Area Marina Protetta. La femmina adulta (stimata 2,5 m e 270 kg) è rimasta sulla spiaggia circa 40 minuti prima di rientrare in mare.', 'uploads/media/foca_lampedusa.jpg', '2026-02-01', 5891, 5),
(9, 'Banco di ricciole in caccia — Bonifacio', 'Gruppo di 15–20 ricciole adulte (Seriola dumerili) che cacciano cooperativamente un banco di lanzardi nello Stretto di Bonifacio. La caccia segue un pattern stereotipato: le ricciole si avvicinano lateralmente compattando il banco, poi attaccano simultaneamente dal basso a oltre 30 km/h.', 'uploads/media/ricciole_caccia.jpg', '2025-09-08', 1677, 1),
(10, 'Cavallucci marini in corteggiamento — Portofino', 'Coppia di Hippocampus guttulatus durante la fase di corteggiamento nelle praterie di Posidonia dell\'Area Marina Protetta di Portofino. I due esemplari si ancorano sullo stesso filo d\'alga e sincronizzano i movimenti in una \"danza\" che può durare ore. La femmina depositerà le uova nella tasca del maschio.', 'uploads/media/cavallucci_portofino.jpg', '2025-06-18', 3456, 6),
(11, 'Murena in caccia notturna — grotte di Capri', 'Sequenza notturna (luce rossa) di una murena (Muraena helena) che emerge per cacciare. Si vede chiaramente il trail olfattivo seguito per localizzare un polpo. Si documenta per la prima volta in campo aperto l\'estensione delle mascelle faringo-branchiali per trascinare la preda nella tana.', 'uploads/media/murena_caccia.jpg', '2025-05-30', 1089, 7),
(12, 'Aggregamento riproduttivo di tonno rosso — Canale di Sicilia', 'Ripresa aerea con drone a 80 m di quota di un aggregamento riproduttivo di Thunnus thynnus nel Canale di Sicilia, durante la campagna PassiveTuna. Stimati 400–600 individui adulti. Si vedono i salti tipici del corteggiamento. Il drone è rimasto in quota 18 minuti senza disturbare gli animali.', 'uploads/media/tonno_rosso_drone.jpg', '2025-05-22', 6210, 7),
(13, 'La verità sulle stelle marine', 'Non sono mai stata così tanto affascinata e delusa da una stella marina', 'https://vm.tiktok.com/ZNR5Gburo/', '2026-02-25', 0, 9);

-- --------------------------------------------------------

--
-- Struttura della tabella `minaccia`
--

CREATE TABLE `minaccia` (
  `id_minaccia` int(11) NOT NULL,
  `nome` varchar(100) DEFAULT NULL,
  `tipo` varchar(50) DEFAULT NULL,
  `descrizione` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struttura della tabella `news`
--

CREATE TABLE `news` (
  `id_news` int(11) NOT NULL,
  `titolo` varchar(150) DEFAULT NULL,
  `contenuto` text DEFAULT NULL,
  `copertina` text DEFAULT NULL,
  `data_pub` date DEFAULT NULL,
  `visualizzazioni` int(11) DEFAULT NULL,
  `id_ricercatore` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dump dei dati per la tabella `news`
--

INSERT INTO `news` (`id_news`, `titolo`, `contenuto`, `copertina`, `data_pub`, `visualizzazioni`, `id_ricercatore`) VALUES
(1, 'Pod di 200 delfini comuni avvistato nello Stretto di Messina', 'Un gruppo eccezionale di circa 200 delfini comuni (Delphinus delphis) è stato documentato il 14 gennaio 2026 nello Stretto di Messina durante una campagna di monitoraggio acustico condotta dalla nave oceanografica Urania del CNR. Il \"megapod\" è rimasto nelle acque dello Stretto per circa sei ore, cacciando cooperativamente acciughe nell\'area di risalita d\'acqua fredda tipica del canale.\n\nLo Stretto di Messina è una delle aree di più alta biodiversità del Mediterraneo per via della corrente bidirezionale che porta nutrienti dagli strati profondi in superficie. I delfini comuni sfruttano questo fenomeno ogni inverno.\n\nIl rilevamento è stato effettuato tramite idrofoni passivi a 50 m di profondità combinati con osservazioni visive dal ponte. I ricercatori hanno registrato oltre 12.000 click di ecolocazione in cinque ore, permettendo l\'analisi del pattern di caccia cooperativa. I dati mostrano che i delfini dividono il banco di prede in sottogruppi sempre più piccoli mediante \"herding\", spingendo il banco verso la superficie prima di attaccarlo a turno.\n\nIl gruppo includeva esemplari adulti, giovani dell\'anno e almeno due femmine con piccoli lattanti affiancati. Le immagini sono state catalogate per photo-ID e inserite nel database CetaBase del CNR-ISMAR.', 'uploads/news/news_delfini_messina.jpg', '2026-01-16', 893, 1),
(2, 'Nidificazione record di Caretta caretta sul litorale calabrese: 48 nidi', 'La stagione riproduttiva 2025 della tartaruga marina comune (Caretta caretta) sulle coste calabresi si è conclusa con un risultato record: 48 nidi documentati tra il litorale ionico di Crotone e Capo Rizzuto, con un tasso di schiusa medio dell\'82%. I dati raccolti da ISPRA mostrano un incremento del 34% rispetto alla media 2020–2024.\n\nLe deposizioni sono avvenute tra giugno e agosto, con temperatura dei nidi compresa tra 28°C e 32°C. Quest\'anno il 74% dei neonati è risultato di sesso femminile — una sbilanciatura del sex-ratio dovuta alle temperature elevate che preoccupa per il lungo termine.\n\nSono stati liberati in mare 3.847 neonati nel periodo agosto–ottobre. Grazie al telerilevamento GPS applicato a tre femmine nidificanti, è stato possibile tracciare i loro spostamenti post-riproduttivi: due esemplari si sono diretti verso la Grecia, uno verso la Turchia.\n\n\"I palangari e le reti da imbrocco continuano a essere la principale causa di mortalità accidentale nel Mediterraneo, con circa 40.000 esemplari catturati involontariamente ogni anno\", sottolinea il dott. Mario Rossi di ISPRA.', 'uploads/news/news_tartaruga_calabria.jpg', '2025-11-03', 1243, 5),
(3, 'Declino delle praterie di Posidonia nel Golfo di Napoli: -23% in 15 anni', 'Uno studio pubblicato su Marine Ecology Progress Series documenta il declino pluridecennale delle praterie di Posidonia oceanica nel Golfo di Napoli con metodi di telerilevamento iperspettrale. L\'analisi di immagini satellitari WorldView-3 integrate con transetti subacquei ha rilevato una perdita del 23% della superficie delle praterie tra il 2008 e il 2023.\n\nLe cause identificate sono molteplici: l\'ancoraggio non regolamentato di imbarcazioni da diporto (stimato in 180.000 ancoraggi/anno nel solo golfo), il run-off di nutrienti dai corsi d\'acqua e l\'avanzata dell\'alga invasiva Caulerpa cylindracea nelle aree marginali.\n\n\"Ogni ettaro di prateria di Posidonia che scompare equivale alla perdita di una nursery per circa 40 specie ittiche, alla liberazione di 838 kg di carbonio sequestrato e alla rimozione di un sistema di protezione naturale della costa\", spiega la prof.ssa Federica Marini dell\'Università di Bologna.\n\nLo studio propone l\'istituzione di zone di ancoraggio obbligatorio con corpo morto nelle aree marine protette e l\'estensione del divieto di ancoraggio alle imbarcazioni superiori ai 12 m in tutta la fascia 0–30 m del golfo.', 'uploads/news/news_posidonia_napoli.jpg', '2025-09-22', 2187, 6),
(4, 'Prima riproduzione di foca monaca nelle acque sarde dopo 40 anni', 'Il 3 marzo 2026 i ricercatori del CNR-ISMAR hanno confermato la prima nascita documentata di un cucciolo di foca monaca (Monachus monachus) in acque sarde dagli anni \'80 del Novecento. Il cucciolo — un maschio di circa 25 kg battezzato \"Ulisse\" — è stato fotografato all\'interno di una grotta marina nell\'area del Parco Nazionale dell\'Asinara grazie a una trappola fotografica subacquea.\n\nLa presenza stabile di una femmina adulta nell\'area era nota dal 2021. Nell\'estate 2025 i sensori acustici avevano rilevato vocalizzazioni tipiche del corteggiamento, suggerendo la presenza di un maschio nell\'area.\n\nLa notizia assume un significato storico: la Sardegna era stata per secoli l\'habitat principale della foca monaca in Italia, con una colonia documentata fino agli anni \'70. La persecuzione attiva da parte dei pescatori ne aveva causato l\'estinzione locale.\n\n\"Questa nascita è il risultato di vent\'anni di protezione delle coste dell\'Asinara. Ma la popolazione è ancora fragilissima: un singolo evento può vanificare anni di lavoro\", sottolinea il dott. Canepari.', 'uploads/news/news_foca_sardegna.jpg', '2026-03-05', 3413, 1),
(5, 'Rilevamento acustico passivo del tonno rosso: nuova tecnica non invasiva', 'Un team congiunto OGS e Università Politecnica delle Marche ha sviluppato un protocollo innovativo per il monitoraggio del tonno rosso (Thunnus thynnus) basato su idrofoni passivi ad alta sensibilità, senza la necessità di cattura o tagging degli esemplari. Lo studio è pubblicato su Journal of Marine Science and Engineering.\n\nIl protocollo, denominato PassiveTuna, prevede il dispiegamento di 12 idrofoni autonomi in un\'area di 200 km² nel Canale di Sicilia durante il picco riproduttivo (maggio–giugno). I segnali vengono analizzati con algoritmi di machine learning con un\'accuratezza del 94,7% nel riconoscere le vocalizzazioni del tonno rosso.\n\nI risultati della prima campagna hanno identificato aggregamenti riproduttivi in tre siti del Canale di Sicilia precedentemente non documentati, con una stima di 2.400–3.100 individui — circa il 12% della popolazione del Mediterraneo occidentale.\n\n\"Il monitoraggio acustico passivo ha un costo inferiore del 70% rispetto al tagging tradizionale e non causa stress negli esemplari\", spiega la dott.ssa Iris Lagnarini.', 'uploads/news/news_tonno_acustico.jpg', '2026-01-28', 1656, 7),
(6, 'Squalo bianco nel Tirreno: movimenti stagionali documentati per la prima volta', 'Uno studio decennale documenta per la prima volta i movimenti stagionali del grande squalo bianco (Carcharodon carcharias) nel Mar Tirreno. Tra il 2014 e il 2024, il team del CNR-ISMAR ha ottenuto 34 avvistamenti confermati e 12 individui foto-identificati grazie ai pattern unici delle macchie sul fianco.\n\nI dati mostrano una piccola popolazione residente di 15–25 individui che si spostano stagionalmente tra l\'Arcipelago Toscano e la Liguria in estate e le acque di Sardegna e Sicilia in inverno.\n\nLa marcatura satellitare di tre esemplari ha documentato profili di immersione fino a 890 m di profondità. La femmina adulta denominata \"Circe\" (5,2 m) percorre annualmente oltre 4.200 km.\n\n\"Le catture accidentali nelle reti da posta sono la principale minaccia: stimiamo 3–5 catture accidentali l\'anno nel Tirreno, in una popolazione così piccola un numero insostenibile\", afferma il dott. Alessio Canepari.', 'uploads/news/news_squalo_tirreno.jpg', '2025-12-10', 4872, 1),
(7, 'Mappatura 3D dell\'Abisso Calypso: prima batimetria ad alta risoluzione', 'Una campagna oceanografica congiunta OGS-CMRE ha prodotto la prima mappa batimetrica ad alta risoluzione (5×5 m pixel) dell\'Abisso Calypso, la fossa più profonda del Mediterraneo nel Mar Jonio, con profondità massima di 5.267 m. La campagna di 18 giorni ha utilizzato un multibeam echosounder combinato con un AUV per la mappatura sotto i 2.000 m.\n\nLe nuove mappe rivelano caratteristiche precedentemente sconosciute: una serie di \"mounds\" carbonatici alti 80–150 m che potrebbero ospitare comunità di coralli bianchi (Lophelia pertusa), e una piattaforma continentale collassata che suggerisce un evento di frana sottomarina nel Pleistocene.\n\nSono stati raccolti campioni di macrofauna a tre profondità (1.200 m, 2.800 m, 4.500 m): la presenza di invertebrati bentonici fino alla massima profondità campionata conferma la vita negli ambienti abissali del Mediterraneo.\n\n\"L\'Abisso Calypso è praticamente inesplorato. Queste mappe sono solo il punto di partenza\", sottolinea il dott. Michelangelo Boraso.', 'uploads/news/news_abisso_calypso.jpg', '2025-10-14', 987, 4),
(8, 'Cavallucci marini del Mediterraneo: prima stima con eDNA', 'La prima stima della densità di Hippocampus guttulatus nel Mediterraneo basata su DNA ambientale (eDNA) è stata pubblicata su Molecular Ecology Resources. Il metodo consiste nel filtrare campioni d\'acqua marina per raccogliere il DNA rilasciato dagli organismi e nell\'amplificare con PCR primers specifici per la specie.\n\nLo studio ha campionato 240 stazioni in 15 aree marine protette e non protette. I risultati mostrano che la densità è 4,7 volte superiore all\'interno delle aree marine protette rispetto alle aree limitrofe, confermando l\'efficacia della protezione.\n\nLe praterie di Posidonia in buon stato ospitano in media 0,8 individui/m², contro 0,1–0,2 individui/m² nei siti con Posidonia degradata: la perdita di Posidonia comporta un calo dell\'87% nella densità di cavallucci marini.\n\n\"L\'eDNA è rivoluzionario per il monitoraggio di specie criptiche. Fuori dalle zone protette la situazione è molto più preoccupante di quanto si pensasse\", afferma la prof.ssa Federica Marini.', 'uploads/news/news_cavalluccio_edna.jpg', '2026-02-05', 1123, 6),
(9, 'Microplastiche nell\'Adriatico: concentrazione record nel 2025', 'Un monitoraggio sistematico ISPRA nelle acque superficiali dell\'Adriatico ha rilevato nel 2025 concentrazioni medie di 1,4 particelle/m³, con picchi di 8,7 particelle/m³ nelle acque antistanti la foce del Po e le aree portuali. I dati mostrano un incremento del 28% rispetto al 2022.\n\nLe microplastiche analizzate erano principalmente fibre sintetiche da lavaggio di capi d\'abbigliamento (61%), frammenti da degradazione UV (24%) e pellet industriali (15%).\n\nIl 78% delle acciughe e il 93% delle triglie di fango pescate nelle stesse aree aveva ingerito microplastiche. Nei pesci è stata documentata un\'associazione tra la presenza di microplastiche intestinali e valori elevati di marcatori di stress ossidativo nel fegato.\n\n\"Senza interventi strutturali a monte — sistemi di raccolta nei corsi d\'acqua, riduzione dell\'usa e getta — le concentrazioni continueranno a crescere indipendentemente dalle campagne di pulizia costiera\", afferma il dott. Mario Rossi di ISPRA.', 'uploads/news/news_microplastiche_adriatico.jpg', '2026-01-09', 2342, 5),
(10, 'Mimetismo attivo della murena documentato con telecamere ad alta velocità', 'Uno studio pubblicato su Journal of Experimental Biology documenta il comportamento di mimetismo attivo della murena (Muraena helena) mediante telecamere ad alta velocità (1.000 fps). Le riprese rivelano che la specie modifica la colorazione del muso e dell\'area periorbitale in risposta a stimoli visivi e chimici, con tempi di risposta di 180–220 ms.\n\nI ricercatori dell\'Università Politecnica delle Marche hanno esposto murene a substrati di diversa colorazione: su fondale chiaro, la murena schiariva le macchie dorate; su fondale scuro, aumentava il contrasto tra le bande brune e gialle. Il comportamento era più marcato durante le fasi di caccia.\n\nLe analisi istologiche della pelle hanno rivelato melanofori e xantofori in una struttura a strati precedentemente sottovalutata in letteratura. Il meccanismo è analogo a quello dei cefalopodi, sebbene molto più lento.\n\n\"Questi risultati cambiano la nostra comprensione del mimetismo nei pesci ossei elongati. È un esempio di convergenza evolutiva con i polpi\", commenta la dott.ssa Iris Lagnarini.', 'uploads/news/news_murena_mimetismo.jpg', '2025-08-19', 743, 7);

-- --------------------------------------------------------

--
-- Struttura della tabella `osservazione`
--

CREATE TABLE `osservazione` (
  `id_osservazione` int(11) NOT NULL,
  `data` date DEFAULT NULL,
  `id_ricercatore` int(11) DEFAULT NULL,
  `id_specie` int(11) DEFAULT NULL,
  `id_luogo` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struttura della tabella `paese`
--

CREATE TABLE `paese` (
  `id_paese` int(11) NOT NULL,
  `nome` varchar(100) NOT NULL,
  `continente` varchar(100) DEFAULT NULL,
  `cod_iso` char(3) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struttura della tabella `progetto`
--

CREATE TABLE `progetto` (
  `id_pd` int(11) NOT NULL,
  `titolo` varchar(150) DEFAULT NULL,
  `obiettivo` text DEFAULT NULL,
  `budget` float DEFAULT NULL,
  `raccolto` float DEFAULT 0,
  `stato` varchar(50) DEFAULT NULL,
  `data_i` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dump dei dati per la tabella `progetto`
--

INSERT INTO `progetto` (`id_pd`, `titolo`, `obiettivo`, `budget`, `raccolto`, `stato`, `data_i`) VALUES
(1, 'Salviamo la foca monaca', 'Il progetto finanzia il monitoraggio di 6 potenziali grotte riproduttive nell\'Area Marina Protetta dell\'Asinara con 12 trappole fotografiche subacquee, due boe acustiche autonome e una stazione meteo-marina. I dati vengono trasmessi in tempo reale e pubblicati con licenza aperta. Obiettivo: documentare la recolonizzazione spontanea della Sardegna da parte della specie più minacciata del Mediterraneo.', 15000, 10320, 'attivo', '2025-06-01'),
(2, 'Restauro praterie Posidonia adriatica', 'Reimpianto sperimentale di Posidonia oceanica in tre siti del medio Adriatico (Ancona, Vieste, isole Tremiti) degradati dall\'ancoraggio e dal dragaggio. Le piante donatrici vengono coltivate in vivaio per 6 mesi, poi trapiantate con ancoraggi biodegradabili. Il monitoraggio dura 24 mesi e alimenterà le linee guida nazionali per il restauro della Posidonia, attualmente assenti in Italia.', 30000, 4200, 'urgente', '2025-09-15'),
(3, 'Monitoraggio cetacei Adriatico settentrionale', 'Campagna triennale di monitoraggio acustico e visivo dei cetacei nell\'Adriatico, area ad alto rischio per traffico marittimo intenso e inquinamento acustico. Comprende tre idrofoni passivi a profondità diverse, un sistema di videosorveglianza panoramica a 360° e protocolli di citizen science per i pescatori della laguna di Venezia.', 8000, 8000, 'completato', '2024-11-01'),
(4, 'Photo-ID squalo bianco nel Tirreno', 'Costruzione del primo catalogo sistematico di foto-identificazione degli squali bianchi del Mar Tirreno, con algoritmo di intelligenza artificiale Wildbook for Sharks. Tre campagne di 15 giorni ciascuna con la nave CNR-Urania nell\'Arcipelago Toscano, tre shark-cages strumentate e coordinamento della rete europea di avvistamento squali.', 22000, 14700, 'attivo', '2025-03-20'),
(5, 'SentinellaPlastica — microplastiche Adriatico', 'Espansione della rete di monitoraggio delle microplastiche nell\'Adriatico con 8 nuove stazioni automatiche nelle aree marine protette di Miramare, Tegnùe di Chioggia e Tremiti. Dati pubblicati in tempo reale su piattaforma pubblica e contribuiti al database EMODnet europeo. Include un programma di formazione di 40 pescatori come \"sentinelle\".', 18000, 5400, 'attivo', '2025-12-01'),
(6, 'Bioacustica dei reef mediterranei', 'Studio della \"firma acustica\" delle biocenosi coralligene come metodo per valutare la salute degli ecosistemi. Registrazione standardizzata in 36 siti a diverso grado di protezione e sviluppo di un indice acustico correlato con misure di biodiversità. Obiettivo finale: un protocollo di monitoraggio rapido utilizzabile da qualsiasi subacqueo con uno smartphone.', 12000, 2100, 'attivo', '2026-01-10');

-- --------------------------------------------------------

--
-- Struttura della tabella `ricercatore`
--

CREATE TABLE `ricercatore` (
  `id_ricercatore` int(11) NOT NULL,
  `qualifica` varchar(100) DEFAULT NULL,
  `id_ente` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dump dei dati per la tabella `ricercatore`
--

INSERT INTO `ricercatore` (`id_ricercatore`, `qualifica`, `id_ente`) VALUES
(1, 'Ricercatore Senior — Ecologia Marina', 1),
(4, 'Dottorando — Oceanografia Biologica', 2),
(5, 'Tecnico Scientifico — Conservazione Marina', 3),
(6, 'Professoressa Associata — Biologia Marina', 4),
(7, 'Ricercatrice — Cetologia e Bioacustica', 5),
(9, 'Studente Magistrale (tesi)', NULL);

-- --------------------------------------------------------

--
-- Struttura della tabella `richiesta_ricercatore`
--

CREATE TABLE `richiesta_ricercatore` (
  `id_richiesta` int(11) NOT NULL,
  `id_utente` int(11) NOT NULL,
  `ente_dichiarato` varchar(255) DEFAULT NULL,
  `qualifica_dichiarata` varchar(100) DEFAULT NULL,
  `motivazione` text DEFAULT NULL,
  `certificato_path` varchar(255) DEFAULT NULL,
  `badge_path` varchar(255) DEFAULT NULL,
  `stato` enum('in_attesa','approvato','rifiutato') DEFAULT 'in_attesa',
  `data_richiesta` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dump dei dati per la tabella `richiesta_ricercatore`
--

INSERT INTO `richiesta_ricercatore` (`id_richiesta`, `id_utente`, `ente_dichiarato`, `qualifica_dichiarata`, `motivazione`, `certificato_path`, `badge_path`, `stato`, `data_richiesta`) VALUES
(3, 9, 'università di padova', 'Studente Magistrale (tesi)', 'Vorrei usare la piattaforma per creare contenuti informativi', NULL, NULL, 'approvato', '2026-02-25');

-- --------------------------------------------------------

--
-- Struttura della tabella `rilevazione_ambientale`
--

CREATE TABLE `rilevazione_ambientale` (
  `id_rilevazione` int(11) NOT NULL,
  `parametro` varchar(100) DEFAULT NULL,
  `valore` float DEFAULT NULL,
  `data` date DEFAULT NULL,
  `id_ricercatore` int(11) DEFAULT NULL,
  `id_luogo` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struttura della tabella `specie`
--

CREATE TABLE `specie` (
  `id_specie` int(11) NOT NULL,
  `nome` varchar(100) DEFAULT NULL,
  `nome_scientifico` varchar(150) DEFAULT NULL,
  `famiglia` varchar(100) DEFAULT NULL,
  `classe` varchar(100) DEFAULT NULL,
  `dieta` varchar(100) DEFAULT NULL,
  `dimensioni` varchar(50) DEFAULT NULL,
  `peso` float DEFAULT NULL,
  `stato_conservazione` varchar(50) DEFAULT NULL,
  `descrizione` text DEFAULT NULL,
  `immagine` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dump dei dati per la tabella `specie`
--

INSERT INTO `specie` (`id_specie`, `nome`, `nome_scientifico`, `famiglia`, `classe`, `dieta`, `dimensioni`, `peso`, `stato_conservazione`, `descrizione`, `immagine`) VALUES
(1, 'Delfino Comune', 'Delphinus delphis', 'Delphinidae', 'Mammalia', 'Carnivoro', '170-240', 100, 'LC', 'Il delfino comune è probabilmente il cetaceo più abbondante al mondo, con una popolazione stimata in oltre 6 milioni di individui. Vive in gruppi detti \"megapodi\" che possono contare migliaia di individui e che cacciano cooperativamente sardine e acciughe. Nel Mediterraneo è presente soprattutto nello Ionio e nel Mar di Alboran. Ogni individuo possiede un \"fischio-firma\" unico, analogo a un nome proprio, riconoscibile dagli altri membri del gruppo. Vivono fino a 35 anni; le femmine partoriscono un piccolo ogni 1–3 anni dopo una gestazione di 10–11 mesi. Sensibile all\'inquinamento acustico prodotto dal traffico navale, che interferisce con la comunicazione e la navigazione.', 'uploads/specie/delfino_comune.jpg'),
(2, 'Squalo Bianco', 'Carcharodon carcharias', 'Lamnidae', 'Chondrichthyes', 'Carnivoro', '400-600', 1100, 'VU', 'Il grande squalo bianco è il più grande predatore cartilagineo del mondo. Predatore apicale fondamentale per l\'equilibrio degli ecosistemi marini, controlla le popolazioni di foche, tonni e altri pesci. La sua reputazione di \"mangiatore di uomini\" è largamente ingiustificata: la maggior parte degli attacchi è causata da esplorazione curiosa. Classificato \"Vulnerabile\" dalla IUCN principalmente a causa della pesca accidentale nelle reti da posta e della domanda illegale di pinne e mascelle. La maturità sessuale è raggiunta tardi (9–10 anni) e la gestazione dura 11 mesi, rendendo il recupero delle popolazioni molto lento. Nel Mediterraneo è presente sporadicamente, con avvistamenti documentati soprattutto nel Tirreno e nel Canale di Sicilia.', 'uploads/specie/squalo_bianco.jpg'),
(3, 'Tartaruga Caretta', 'Caretta caretta', 'Cheloniidae', 'Reptilia', 'Onnivoro', '70-95', 135, 'VU', 'La tartaruga comune è la specie di tartaruga marina più frequente nel Mediterraneo. Le femmine tornano a deporre le uova sulle stesse spiagge dove sono nate (filotassia). Ogni stagione depone 3–5 nidi con 100–120 uova. La determinazione del sesso nei neonati dipende dalla temperatura di incubazione: sopra i 29°C nascono femmine, sotto i 29°C maschi — fatto che rende questa specie vulnerabile al riscaldamento climatico. Nel Mediterraneo le principali aree di nidificazione sono Grecia, Turchia, Cipro, Libia e Italia meridionale. Il 90% dei decessi documentati è causato dall\'uomo: palangari, reti da imbrocco, ingestione di plastica scambiata per meduse, investimenti da eliche.', 'uploads/specie/tartaruga_caretta.jpg'),
(4, 'Tonno Rosso', 'Thunnus thynnus', 'Scombridae', 'Actinopterygii', 'Carnivoro', '150-300', 450, 'LC', 'Il tonno rosso è il pesce osseo più grande del Mediterraneo e uno dei migliori nuotatori del mondo. Può mantenere la temperatura muscolare fino a 10°C sopra quella dell\'acqua grazie a uno scambiatore di calore controcorrente, permettendo performance elevate anche in acque fredde. Migra ogni anno tra le aree di alimentazione settentrionali e le aree riproduttive nel Mediterraneo. Dopo anni di pesca industriale intensiva, si è parzialmente ripreso grazie a quote ICCAT e sorveglianza satellitare. Nel 2021 la IUCN ha declassato lo status da EN a LC per la popolazione atlantica. Può raggiungere i 70 km/h in brevi scatti.', 'uploads/specie/tonno_rosso.jpg'),
(5, 'Foca Monaca', 'Monachus monachus', 'Phocidae', 'Mammalia', 'Carnivoro', '220-280', 300, 'EN', 'La foca monaca del Mediterraneo è uno dei mammiferi marini più minacciati al mondo con una popolazione totale stimata in meno di 800 individui, distribuiti principalmente tra Grecia, Turchia e coste atlantiche del Marocco. In Italia sopravvivono piccolissime colonie in Sardegna e Sicilia. Il nome deriva dal colore brunastro del mantello, simile all\'abito francescano. Si riproduce in grotte costiere semi-sommerse con camera d\'aria superiore, comportamento evolutosi probabilmente in risposta alle persecuzioni umane. Le principali minacce sono: pesca accidentale, disturbo dei siti riproduttivi, degrado costiero e riduzione delle prede.', 'uploads/specie/foca_monaca.jpg'),
(6, 'Polpo Comune', 'Octopus vulgaris', 'Octopodidae', 'Cephalopoda', 'Carnivoro', '60-90', 3, 'LC', 'Il polpo comune è uno dei molluschi cefalopodi più intelligenti del mondo marino. Possiede circa 500 milioni di neuroni — più di qualsiasi altro invertebrato — di cui i due terzi si trovano nelle ventose dei tentacoli, capaci di elaborazione tattile e chimica autonoma. Maestro del mimetismo: grazie a cromatofori, iridofori e papille cutanee modifica colore, texture e forma del corpo in meno di un secondo. Caccia gamberetti, granchi e molluschi; le prede resistenti vengono aperte con il becco corneo. Le femmine depongono fino a 400.000 uova e le accudiscono per 2–4 settimane senza mangiare, morendo poco dopo la schiusa.', 'uploads/specie/polpo_comune.jpg'),
(7, 'Posidonia Oceanica', 'Posidonia oceanica', 'Posidoniaceae', 'Liliopsida', 'Autotrofo', '—', NULL, 'EN', 'La Posidonia oceanica è una pianta acquatica (non un\'alga) endemica del Mar Mediterraneo. Le sue praterie coprono circa 50.000 km² di fondale e producono ossigeno, sequestrano CO₂, stabilizzano i sedimenti e fungono da nursery per oltre 400 specie di pesci e invertebrati. Le foglie morte formano le \"banquettes\" che proteggono le spiagge dall\'erosione. È un eccellente bioindicatore della qualità delle acque: arretra drasticamente in presenza di inquinamento, ancoraggio di imbarcazioni e pesca a strascico. Cresce di soli 1–6 cm/anno, rendendo il recupero di praterie degradate un processo secolare.', 'uploads/specie/posidonia.jpg'),
(8, 'Murena', 'Muraena helena', 'Muraenidae', 'Actinopterygii', 'Carnivoro', '80-130', 4, 'LC', 'La murena è un pesce dalla forma serpentiforme caratteristico delle coste rocciose del Mediterraneo. Contrariamente alla reputazione, è timida e attacca l\'uomo solo se provocata. Possiede due paia di narici: le anteriori per l\'aspirazione degli odori, le posteriori a tubicino per l\'espirazione, conferendole un\'eccellente capacità olfattiva. Caccia di notte principalmente polpi, crostacei e piccoli pesci. Possiede un secondo set di mascelle faringo-branchiali che può protrarre nel cavo orale per trascinare le prede verso l\'esofago — adattamento unico tra i vertebrati, analogo alla seconda mascella degli Xenomorph della saga Alien.', 'uploads/specie/murena.jpg');

-- --------------------------------------------------------

--
-- Struttura della tabella `specie_minaccia`
--

CREATE TABLE `specie_minaccia` (
  `id_specie` int(11) NOT NULL,
  `id_minaccia` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struttura della tabella `sponsorizzazione`
--

CREATE TABLE `sponsorizzazione` (
  `id_ente` int(11) NOT NULL,
  `id_pd` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struttura della tabella `utente`
--

CREATE TABLE `utente` (
  `id_utente` int(11) NOT NULL,
  `username` varchar(50) DEFAULT NULL,
  `password_hash` text DEFAULT NULL,
  `email` varchar(150) DEFAULT NULL,
  `nome` varchar(100) DEFAULT NULL,
  `cognome` varchar(100) DEFAULT NULL,
  `data_registrazione` date DEFAULT NULL,
  `is_admin` tinyint(1) DEFAULT 0,
  `foto_profilo` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dump dei dati per la tabella `utente`
--

INSERT INTO `utente` (`id_utente`, `username`, `password_hash`, `email`, `nome`, `cognome`, `data_registrazione`, `is_admin`, `foto_profilo`) VALUES
(1, 'flashrin02', '$2y$10$HKZWr.JA5l6PiONcJwXoe.WkKHBPT6xLVBR/LdadA5d1CAaIBepFy', 'alessiocanepari22@gmail.com', 'Alessio', 'Canepari', '2026-01-10', 1, NULL),
(2, 'xronti', '$2y$10$9nUr.ggHzQb5LWVZNIZZAetmEPtDhqAh/4xPYGPKwg9YXMNJ.BtQK', 'samurossi1999@gmail.com', 'Samuele', 'Rossi', '2026-01-15', 0, NULL),
(4, 'borasomichelangelo', '$2y$10$TLn59xvdJ/2eBFPBg4BKyeB0jngtlD0xXYifnLX3QPhdt1vxdeQZa', 'michelangelo.boraso@ogs.it', 'Michelangelo', 'Boraso', '2026-01-20', 0, NULL),
(5, 'mario_ricercatore', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'mario.rossi@ispra.it', 'Mario', 'Rossi', '2026-02-01', 0, NULL),
(6, 'f_marini', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'federica.marini@unibo.it', 'Federica', 'Marini', '2026-02-05', 0, NULL),
(7, 'iris_', '$2y$10$njo3eIRvU7CPUOJg5o1CYu2Du9zC1wt/kA4kJ2qkt/FRQNnUYJ6v.', 'irislagnarini@gmail.com', 'Iris', 'Lagnarini', '2026-02-10', 0, NULL),
(8, 'dive_mario', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'mario.bianchi@gmail.com', 'Mario', 'Bianchi', '2026-02-15', 0, NULL),
(9, 'Flamina_123', '$2y$10$QT9Tso6plCw16ZGsHY02puSlffMl5w2u6i7eHgcnfFWasdFG22q/q', 'flaminascapigliati@gmail.com', 'Flaminia', 'Scapigliati', '2026-02-25', 0, NULL);

--
-- Indici per le tabelle scaricate
--

--
-- Indici per le tabelle `donazione`
--
ALTER TABLE `donazione`
  ADD PRIMARY KEY (`id_donazione`),
  ADD KEY `id_utente` (`id_utente`),
  ADD KEY `id_pd` (`id_pd`);

--
-- Indici per le tabelle `ente_di_ricerca`
--
ALTER TABLE `ente_di_ricerca`
  ADD PRIMARY KEY (`id_ente`),
  ADD KEY `id_paese` (`id_paese`);

--
-- Indici per le tabelle `habitat`
--
ALTER TABLE `habitat`
  ADD PRIMARY KEY (`id_habitat`),
  ADD KEY `id_luogo` (`id_luogo`);

--
-- Indici per le tabelle `like_media`
--
ALTER TABLE `like_media`
  ADD PRIMARY KEY (`id_post`,`id_utente`),
  ADD KEY `id_utente` (`id_utente`);

--
-- Indici per le tabelle `luogo`
--
ALTER TABLE `luogo`
  ADD PRIMARY KEY (`id_luogo`),
  ADD KEY `id_paese` (`id_paese`);

--
-- Indici per le tabelle `media`
--
ALTER TABLE `media`
  ADD PRIMARY KEY (`id_post`);

--
-- Indici per le tabelle `minaccia`
--
ALTER TABLE `minaccia`
  ADD PRIMARY KEY (`id_minaccia`);

--
-- Indici per le tabelle `news`
--
ALTER TABLE `news`
  ADD PRIMARY KEY (`id_news`),
  ADD KEY `id_ricercatore` (`id_ricercatore`);

--
-- Indici per le tabelle `osservazione`
--
ALTER TABLE `osservazione`
  ADD PRIMARY KEY (`id_osservazione`),
  ADD KEY `id_ricercatore` (`id_ricercatore`),
  ADD KEY `id_specie` (`id_specie`),
  ADD KEY `id_luogo` (`id_luogo`);

--
-- Indici per le tabelle `paese`
--
ALTER TABLE `paese`
  ADD PRIMARY KEY (`id_paese`);

--
-- Indici per le tabelle `progetto`
--
ALTER TABLE `progetto`
  ADD PRIMARY KEY (`id_pd`);

--
-- Indici per le tabelle `ricercatore`
--
ALTER TABLE `ricercatore`
  ADD PRIMARY KEY (`id_ricercatore`),
  ADD KEY `id_ente` (`id_ente`);

--
-- Indici per le tabelle `richiesta_ricercatore`
--
ALTER TABLE `richiesta_ricercatore`
  ADD PRIMARY KEY (`id_richiesta`),
  ADD KEY `id_utente` (`id_utente`);

--
-- Indici per le tabelle `rilevazione_ambientale`
--
ALTER TABLE `rilevazione_ambientale`
  ADD PRIMARY KEY (`id_rilevazione`),
  ADD KEY `id_ricercatore` (`id_ricercatore`),
  ADD KEY `id_luogo` (`id_luogo`);

--
-- Indici per le tabelle `specie`
--
ALTER TABLE `specie`
  ADD PRIMARY KEY (`id_specie`);

--
-- Indici per le tabelle `specie_minaccia`
--
ALTER TABLE `specie_minaccia`
  ADD PRIMARY KEY (`id_specie`,`id_minaccia`),
  ADD KEY `id_minaccia` (`id_minaccia`);

--
-- Indici per le tabelle `sponsorizzazione`
--
ALTER TABLE `sponsorizzazione`
  ADD PRIMARY KEY (`id_ente`,`id_pd`),
  ADD KEY `id_pd` (`id_pd`);

--
-- Indici per le tabelle `utente`
--
ALTER TABLE `utente`
  ADD PRIMARY KEY (`id_utente`),
  ADD UNIQUE KEY `username` (`username`);

--
-- AUTO_INCREMENT per le tabelle scaricate
--

--
-- AUTO_INCREMENT per la tabella `donazione`
--
ALTER TABLE `donazione`
  MODIFY `id_donazione` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT per la tabella `media`
--
ALTER TABLE `media`
  MODIFY `id_post` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT per la tabella `news`
--
ALTER TABLE `news`
  MODIFY `id_news` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT per la tabella `progetto`
--
ALTER TABLE `progetto`
  MODIFY `id_pd` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT per la tabella `richiesta_ricercatore`
--
ALTER TABLE `richiesta_ricercatore`
  MODIFY `id_richiesta` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT per la tabella `specie`
--
ALTER TABLE `specie`
  MODIFY `id_specie` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT per la tabella `utente`
--
ALTER TABLE `utente`
  MODIFY `id_utente` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- Limiti per le tabelle scaricate
--

--
-- Limiti per la tabella `donazione`
--
ALTER TABLE `donazione`
  ADD CONSTRAINT `donazione_ibfk_1` FOREIGN KEY (`id_utente`) REFERENCES `utente` (`id_utente`),
  ADD CONSTRAINT `donazione_ibfk_2` FOREIGN KEY (`id_pd`) REFERENCES `progetto` (`id_pd`);

--
-- Limiti per la tabella `ente_di_ricerca`
--
ALTER TABLE `ente_di_ricerca`
  ADD CONSTRAINT `ente_di_ricerca_ibfk_1` FOREIGN KEY (`id_paese`) REFERENCES `paese` (`id_paese`);

--
-- Limiti per la tabella `habitat`
--
ALTER TABLE `habitat`
  ADD CONSTRAINT `habitat_ibfk_1` FOREIGN KEY (`id_luogo`) REFERENCES `luogo` (`id_luogo`);

--
-- Limiti per la tabella `like_media`
--
ALTER TABLE `like_media`
  ADD CONSTRAINT `like_media_ibfk_1` FOREIGN KEY (`id_post`) REFERENCES `media` (`id_post`),
  ADD CONSTRAINT `like_media_ibfk_2` FOREIGN KEY (`id_utente`) REFERENCES `utente` (`id_utente`);

--
-- Limiti per la tabella `luogo`
--
ALTER TABLE `luogo`
  ADD CONSTRAINT `luogo_ibfk_1` FOREIGN KEY (`id_paese`) REFERENCES `paese` (`id_paese`);

--
-- Limiti per la tabella `news`
--
ALTER TABLE `news`
  ADD CONSTRAINT `news_ibfk_1` FOREIGN KEY (`id_ricercatore`) REFERENCES `ricercatore` (`id_ricercatore`);

--
-- Limiti per la tabella `osservazione`
--
ALTER TABLE `osservazione`
  ADD CONSTRAINT `osservazione_ibfk_1` FOREIGN KEY (`id_ricercatore`) REFERENCES `ricercatore` (`id_ricercatore`),
  ADD CONSTRAINT `osservazione_ibfk_2` FOREIGN KEY (`id_specie`) REFERENCES `specie` (`id_specie`),
  ADD CONSTRAINT `osservazione_ibfk_3` FOREIGN KEY (`id_luogo`) REFERENCES `luogo` (`id_luogo`);

--
-- Limiti per la tabella `ricercatore`
--
ALTER TABLE `ricercatore`
  ADD CONSTRAINT `ricercatore_ibfk_1` FOREIGN KEY (`id_ente`) REFERENCES `ente_di_ricerca` (`id_ente`);

--
-- Limiti per la tabella `richiesta_ricercatore`
--
ALTER TABLE `richiesta_ricercatore`
  ADD CONSTRAINT `richiesta_ricercatore_ibfk_1` FOREIGN KEY (`id_utente`) REFERENCES `utente` (`id_utente`) ON DELETE CASCADE;

--
-- Limiti per la tabella `rilevazione_ambientale`
--
ALTER TABLE `rilevazione_ambientale`
  ADD CONSTRAINT `rilevazione_ambientale_ibfk_1` FOREIGN KEY (`id_ricercatore`) REFERENCES `ricercatore` (`id_ricercatore`),
  ADD CONSTRAINT `rilevazione_ambientale_ibfk_2` FOREIGN KEY (`id_luogo`) REFERENCES `luogo` (`id_luogo`);

--
-- Limiti per la tabella `specie_minaccia`
--
ALTER TABLE `specie_minaccia`
  ADD CONSTRAINT `specie_minaccia_ibfk_1` FOREIGN KEY (`id_specie`) REFERENCES `specie` (`id_specie`),
  ADD CONSTRAINT `specie_minaccia_ibfk_2` FOREIGN KEY (`id_minaccia`) REFERENCES `minaccia` (`id_minaccia`);

--
-- Limiti per la tabella `sponsorizzazione`
--
ALTER TABLE `sponsorizzazione`
  ADD CONSTRAINT `sponsorizzazione_ibfk_1` FOREIGN KEY (`id_ente`) REFERENCES `ente_di_ricerca` (`id_ente`),
  ADD CONSTRAINT `sponsorizzazione_ibfk_2` FOREIGN KEY (`id_pd`) REFERENCES `progetto` (`id_pd`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
