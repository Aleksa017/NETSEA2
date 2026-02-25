-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Creato il: Feb 25, 2026 alle 20:48
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
(1, 1),
(2, 1),
(3, 1),
(3, 7),
(4, 1),
(4, 7),
(6, 7);

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
(1, 'Delfino comune in acque siciliane', 'Riprese subacquee di un branco di delfini comuni', 'https://example.com/video1', '2026-02-24', NULL, NULL),
(2, 'Foto tartaruga caretta caretta', 'Nidificazione sulla spiaggia di Lampedusa', 'https://example.com/foto1', '2026-02-21', NULL, NULL),
(3, 'Squalo bianco avvistato', 'Video raro di Carcharodon carcharias nel Tirreno', 'https://example.com/video2', '2026-02-19', NULL, NULL),
(4, 'Squalo bianco nell\'Arcipelago Toscano', 'Avvistamento raro di Carcharodon carcharias a 12 miglia dall\'Isola del Giglio. L\'esemplare, stimato sui 4 metri, è stato fotografato durante una campagna di monitoraggio cetacei.', 'https://upload.wikimedia.org/wikipedia/commons/thumb/5/56/White_shark.jpg/1280px-White_shark.jpg', '2026-02-23', 312, 1),
(5, 'Biocenosi corallina a 40m — Baia di Capri', 'Documentazione fotografica di una prateria di gorgonie rosse (Paramuricea clavata) nel tratto marino del Parco Nazionale del Vesuvio. Profondità: 38-42 metri.', 'https://upload.wikimedia.org/wikipedia/commons/thumb/5/5c/Coral_reef_at_palmyra.jpg/1280px-Coral_reef_at_palmyra.jpg', '2026-02-22', 198, 1),
(6, 'Pod di delfini in migrazione — Stretto di Messina', 'Un gruppo di circa 30 esemplari di Stenella coeruleoalba attraversa lo Stretto durante la migrazione primaverile. Rilevati tramite idrofoni a 2km di distanza.', 'https://upload.wikimedia.org/wikipedia/commons/thumb/1/10/Tursiops_truncatus_01.jpg/1280px-Tursiops_truncatus_01.jpg', '2026-02-21', 445, 1),
(7, 'Nidificazione della caretta caretta sul litorale calabrese', 'Documentazione di un nido di tartaruga Caretta caretta sulla spiaggia di Brancaleone (RC). Identificati 112 uova, schiusa prevista tra 55-60 giorni.', 'https://upload.wikimedia.org/wikipedia/commons/thumb/a/a7/Camponotus_flavomarginatus_ant.jpg/640px-Camponotus_flavomarginatus_ant.jpg', '2026-02-20', 267, 1),
(8, 'Abisso Calypso: immagini dal fondale del Med', 'Prima campagna ROV nella fossa Calypso (5121m), il punto più profondo del Mediterraneo. Rilevate specie di policheti e anfipodi mai catalogati prima.', 'https://upload.wikimedia.org/wikipedia/commons/thumb/3/3f/Bilateria_underwater.jpg/1280px-Bilateria_underwater.jpg', '2026-02-19', 189, 1),
(9, 'Polpo mimetizzato su fondale sabbioso', 'Sequenza fotografica di Octopus vulgaris durante una tecnica di caccia attiva su substrato sabbioso a 8 metri di profondità al largo di Portofino.', 'https://upload.wikimedia.org/wikipedia/commons/thumb/a/aa/Octopus3.jpg/1280px-Octopus3.jpg', '2026-02-18', 523, 1),
(10, 'Prateria di Posidonia oceanica — Isole Egadi', 'Monitoraggio annuale della prateria di Posidonia nel sito Natura 2000 IT9110015. La densità dei fasci fogliari risulta stabile rispetto al rilevamento 2023.', 'https://upload.wikimedia.org/wikipedia/commons/thumb/5/5c/Coral_reef_at_palmyra.jpg/640px-Coral_reef_at_palmyra.jpg', '2026-02-17', 134, 1),
(11, 'Foca monaca avvistata a Lampedusa', 'Un esemplare di Monachus monachus fotografato all\'imboccatura di una grotta marina sul versante sud-ovest dell\'isola. Ultima segnalazione certificata nell\'area risaliva al 2019.', 'https://upload.wikimedia.org/wikipedia/commons/thumb/1/10/Tursiops_truncatus_01.jpg/640px-Tursiops_truncatus_01.jpg', '2026-02-16', 891, 1);

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
(1, 'Avvistato un delfino comune nel Golfo di Napoli', 'Un esemplare di Delphinus delphis è stato avvistato stamane a poche miglia dalla costa partenopea.', NULL, '2026-02-23', NULL, 4),
(2, 'La foca monaca torna in Sicilia dopo 30 anni', 'Straordinario avvistamento al largo delle coste siciliane: una foca monaca fotografata nei pressi di Marettimo.', NULL, '2026-02-16', NULL, 4),
(3, 'Avvistato un delfino comune nel Golfo di Napoli', 'Un esemplare di Delphinus delphis è stato avvistato a poche miglia dalla costa partenopea. Il delfino sembrava in buone condizioni di salute.', NULL, '2026-02-24', NULL, 4),
(4, 'La foca monaca torna in Sicilia dopo 30 anni', 'Una foca monaca fotografata nei pressi di Marettimo. Ultima segnalazione certificata nel 1994.', NULL, '2026-02-17', NULL, 4),
(5, 'Nuovo studio sul tonno rosso nel Mediterraneo', 'I dati del censimento 2024 mostrano un incremento del 12% della popolazione di Thunnus thynnus.', NULL, '2026-02-10', NULL, 4),
(6, 'Temperatura degli oceani raggiunge il record storico nel 2024', 'I ricercatori confermano un aumento di 0.3°C rispetto alla media del secolo. Le conseguenze per la biodiversità marina potrebbero essere irreversibili.\n\nIl monitoraggio satellitare condotto dal CNR-ISMAR ha rilevato temperature superficiali degli oceani mai registrate prima nella storia delle misurazioni moderne. Il fenomeno è distribuito su tutte le principali aree oceaniche, con picchi nel Mediterraneo e nell\'Atlantico settentrionale.\n\nI modelli climatici prevedono un ulteriore incremento di 0.2°C entro il 2030 se le emissioni di CO₂ non verranno ridotte significativamente.', NULL, '2025-02-15', 142, 1),
(7, 'Sbiancamento di massa: il 60% della Grande Barriera Corallina colpita', 'La quarta ondata di sbiancamento massiccio in 8 anni ha investito le barriere coralline australiane. I biologi parlano di punto di non ritorno.\n\nLe temperature record dell\'acqua hanno causato lo stress termico nei coralli, costringendoli ad espellere le alghe simbiotiche che forniscono loro nutrienti e colore. Il fenomeno, noto come sbiancamento, porta alla morte dei coralli se le temperature non tornano nella norma entro poche settimane.\n\nSolo il 40% della barriera ha resistito indenne a quest\'ultimo episodio, rispetto all\'80% che era sopravvissuto al primo grande sbiancamento del 1998.', NULL, '2025-02-12', 100, 1),
(8, 'Nuova specie di cefalopode bioluminescente scoperta nel Mar Ionio', 'Il team del CNR-ISMAR ha identificato nelle profondità ioniche una specie di polpo mai catalogata prima, capace di emettere luce blu-verde.\n\nLa scoperta è avvenuta durante una campagna di esplorazione a 800 metri di profondità al largo delle coste calabresi. L\'animale, provvisoriamente denominato Octopus ionicus luminescens, presenta organi fotofori distribuiti lungo tutti i tentacoli.\n\nLo studio è stato pubblicato sulla rivista Marine Biology ed è già stato citato da 34 pubblicazioni scientifiche internazionali.', NULL, '2025-02-08', 207, 1),
(9, 'La popolazione di balene azzurre mostra segni di ripresa nell\'Atlantico', 'Dopo decenni di caccia intensiva, il censimento 2024 rivela un aumento del 12% degli esemplari nel Nord Atlantico: una rara buona notizia per gli oceani.\n\nIl censimento è stato condotto tramite identificazione fotografica delle macchie pigmentarie uniche di ogni individuo, tecnica che permette di tracciare gli spostamenti delle singole balene nel corso degli anni.\n\nLa ripresa della popolazione è attribuita principalmente all\'efficacia delle zone di protezione istituite negli anni \'90 e alla riduzione del traffico navale nelle rotte di migrazione.', NULL, '2025-02-03', 88, 1),
(10, 'Microplastiche rilevate nel sangue di cetacei del Mediterraneo', 'Studio dell\'Università di Palermo rileva concentrazioni preoccupanti di particelle di plastica in campioni ematici di delfini e balene del Tirreno.\n\nI campioni di sangue sono stati prelevati da 42 cetacei durante operazioni di soccorso e rilascio coordinate con il Centro Recupero Tartarughe Marine. In tutti i campioni analizzati sono state trovate microplastiche di dimensioni comprese tra 1 e 500 micrometri.\n\nLe specie più colpite risultano essere il delfino comune (Delphinus delphis) e il tursiope (Tursiops truncatus), entrambi ai vertici della catena alimentare mediterranea.', NULL, '2025-01-28', 173, 1),
(11, 'Acidificazione degli oceani: impatti a cascata sulle catene alimentari marine', 'Il pH medio degli oceani è sceso di 0.1 unità rispetto all\'era preindustriale. I modelli predicono effetti devastanti sulle catene trofiche entro il 2050.\n\nL\'assorbimento di CO₂ atmosferica da parte degli oceani ha causato una progressiva acidificazione delle acque, che compromette la capacità degli organismi marini di formare gusci e scheletri calcarei. Molluschi, echinodermi e coralli sono tra le categorie più a rischio.\n\nLe conseguenze si ripercuotono sull\'intera catena alimentare marina: il collasso delle popolazioni di zooplancton calcificante potrebbe ridurre drasticamente le risorse disponibili per pesci, cetacei e uccelli marini.', NULL, '2025-01-22', 115, 1);

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
(1, 'Salviamo la foca monaca', 'Protezione degli habitat costieri per la foca monaca nel Mediterraneo orientale', 15004, 10000, 'attivo', '2026-02-24'),
(2, 'Monitoraggio delfini Adriatico', 'Campagna di monitoraggio acustico dei cetacei nell Adriatico', 8000, 0, 'attivo', '2026-01-25'),
(3, 'Restauro Posidonia Adriatica', 'Reimpianto delle praterie di Posidonia oceanica lungo le coste adriatiche', 30000, 0, 'urgente', '2026-02-10');

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
(1, 'Dottoressa di Ricerca - CNR-ISMAR', NULL),
(4, 'Studente Magistrale (tesi)', NULL),
(5, 'Ricercatore CNR', NULL),
(7, 'Assegnista di Ricerca', NULL);

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
(1, 4, 'università di padova', 'Studente Magistrale (tesi)', 'yyy', 'uploads/certificati/4_cert.jpg', 'uploads/badge/4_badge.jpg', 'approvato', '2026-02-23'),
(2, 7, 'università di padova', 'Assegnista di Ricerca', 'wddd', NULL, NULL, 'approvato', '2026-02-25');

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
(1, 'Delfino Comune', 'Delphinus delphis', 'Delphinidae', 'Mammalia', 'Carnivoro', '170-240', 70, 'LC', 'Specie gregaria e vivace, il delfino comune è uno dei cetacei più diffusi nel Mediterraneo. Forma gruppi numerosi e si nutre principalmente di pesci e calamari. Sensibile all\'inquinamento acustico e alle reti da pesca.', NULL),
(2, 'Squalo Bianco', 'Carcharodon carcharias', 'Lamnidae', 'Chondrichthyes', 'Carnivoro', '400-600', 1100, 'VU', 'Apice della catena alimentare marina. Nonostante la cattiva reputazione, attacca raramente l\'uomo. Minacciato da pesca accidentale e degrado degli habitat. Presenza sporadica nel Mediterraneo.', NULL),
(3, 'Tartaruga Caretta', 'Caretta caretta', 'Cheloniidae', 'Reptilia', 'Onnivoro', '70-95', 135, 'VU', 'La tartaruga marina più comune nel Mediterraneo. Nidifica sulle spiagge sabbiose e può vivere fino a 80 anni. Principale minaccia: plastica in mare, reti da pesca e illuminazione artificiale sulle spiagge di nidificazione.', NULL),
(4, 'Tonno Rosso', 'Thunnus thynnus', 'Scombridae', 'Actinopterygii', 'Carnivoro', '200-300', 450, 'EN', 'Uno dei pesci più veloci dell\'oceano, raggiunge i 70 km/h. Soggetto a pesca intensiva per decenni, è in lenta ripresa grazie alle quote internazionali imposte dall\'ICCAT. Migra attraverso l\'Atlantico e il Mediterraneo.', NULL),
(5, 'Foca Monaca', 'Monachus monachus', 'Phocidae', 'Mammalia', 'Carnivoro', '220-240', 300, 'CR', 'Tra i mammiferi marini più rari al mondo, con meno di 700 esemplari rimasti. Vive in grotte marine inaccessibili. Principale minaccia: disturbo umano, pesca e degrado degli habitat costieri nel Mediterraneo orientale.', NULL),
(6, 'Polpo Comune', 'Octopus vulgaris', 'Octopodidae', 'Cephalopoda', 'Carnivoro', '60-90', 3, 'LC', 'Mollusco cefalopode dalla straordinaria intelligenza. Capace di cambiare colore e texture della pelle in millisecondi per mimetizzarsi. Vive nei fondali rocciosi e sabbiosi del Mediterraneo, nelle zone costiere.', NULL),
(7, 'Posidonia Oceanica', 'Posidonia oceanica', 'Posidoniaceae', 'Liliopsida', 'Autotrofo', '—', NULL, 'EN', 'Pianta marina endemica del Mediterraneo, fondamentale per la biodiversità costiera. Produce ossigeno, ospita centinaia di specie e protegge le coste dall\'erosione. Le sue praterie sono in forte regressione.', NULL),
(8, 'Murena Helena', 'Muraena helena', 'Muraenidae', 'Actinopterygii', 'Carnivoro', '80-130', 4, 'LC', 'Pesce serpentiforme dai denti aguzzi, vive nelle fessure dei fondali rocciosi. Nonostante l\'aspetto minaccioso è timida e attacca solo se provocata. Presente in tutto il Mediterraneo fino a 80m di profondità.', NULL);

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
(1, 'flashrin02', '$2y$10$HKZWr.JA5l6PiONcJwXoe.WkKHBPT6xLVBR/LdadA5d1CAaIBepFy', 'alessiocanepari22@gmail.com', 'ALESSIO', 'CANEPARI', '2026-02-23', 1, NULL),
(2, 'xronti', '$2y$10$9nUr.ggHzQb5LWVZNIZZAetmEPtDhqAh/4xPYGPKwg9YXMNJ.BtQK', 'samurossi1999@gmail.com', 'Samuele', 'Rossi', '2026-02-23', 0, NULL),
(4, 'borasomichelangelo', '$2y$10$TLn59xvdJ/2eBFPBg4BKyeB0jngtlD0xXYifnLX3QPhdt1vxdeQZa', 'francescoschiatto@gmail.com', 'michelangelo', 'Boraso', '2026-02-23', 0, NULL),
(5, 'mario_ricercatore', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'ricerca@demo.it', 'Mario', 'Rossi', '2026-02-23', 0, NULL),
(7, 'iris_', '$2y$10$njo3eIRvU7CPUOJg5o1CYu2Du9zC1wt/kA4kJ2qkt/FRQNnUYJ6v.', 'irislagnarini@gmail.com', 'Iris', 'Lagnarini', '2026-02-25', 0, NULL);

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
  MODIFY `id_donazione` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT per la tabella `media`
--
ALTER TABLE `media`
  MODIFY `id_post` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT per la tabella `news`
--
ALTER TABLE `news`
  MODIFY `id_news` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT per la tabella `progetto`
--
ALTER TABLE `progetto`
  MODIFY `id_pd` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT per la tabella `richiesta_ricercatore`
--
ALTER TABLE `richiesta_ricercatore`
  MODIFY `id_richiesta` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT per la tabella `specie`
--
ALTER TABLE `specie`
  MODIFY `id_specie` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT per la tabella `utente`
--
ALTER TABLE `utente`
  MODIFY `id_utente` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

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
