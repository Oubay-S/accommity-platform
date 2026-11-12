--
-- PostgreSQL database dump
--


-- Dumped from database version 18.0
-- Dumped by pg_dump version 18.0

SET statement_timeout = 0;
SET lock_timeout = 0;
SET idle_in_transaction_session_timeout = 0;
SET transaction_timeout = 0;
SET client_encoding = 'UTF8';
SET standard_conforming_strings = on;
SELECT pg_catalog.set_config('search_path', '', false);
SET check_function_bodies = false;
SET xmloption = content;
SET client_min_messages = warning;
SET row_security = off;

--
-- Name: enum_statut_litige; Type: TYPE; Schema: public; Owner: postgres
--

CREATE TYPE public.enum_statut_litige AS ENUM (
    'En cours',
    'Clôturé'
);


ALTER TYPE public.enum_statut_litige OWNER TO postgres;

--
-- Name: enum_statut_verif; Type: TYPE; Schema: public; Owner: postgres
--

CREATE TYPE public.enum_statut_verif AS ENUM (
    'En cours de traitement',
    'Validé',
    'rejeté'
);


ALTER TYPE public.enum_statut_verif OWNER TO postgres;

SET default_tablespace = '';

SET default_table_access_method = heap;

--
-- Name: administrateurs; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.administrateurs (
    id_admin uuid NOT NULL,
    id_utilisateur uuid NOT NULL,
    email character varying(255) NOT NULL,
    mot_de_passe character varying(255) NOT NULL,
    derniere_connexion timestamp(0) without time zone NOT NULL
);


ALTER TABLE public.administrateurs OWNER TO postgres;

--
-- Name: annonces; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.annonces (
    id_annonce uuid NOT NULL,
    id_hote uuid NOT NULL,
    id_categorie uuid NOT NULL,
    id_politique uuid,
    titre character varying(255) NOT NULL,
    description text NOT NULL,
    photo_url text NOT NULL,
    type_logement character varying(255) NOT NULL,
    adresse character varying(255) NOT NULL,
    capacite smallint NOT NULL,
    tarif_nuit numeric(10,2) NOT NULL,
    mode_reservation character varying(255) NOT NULL,
    statut character varying(255) NOT NULL,
    equipements text NOT NULL,
    reglement_interieur text,
    date_creation timestamp(0) without time zone NOT NULL,
    CONSTRAINT annonces_mode_reservation_check CHECK (((mode_reservation)::text = ANY ((ARRAY['réservation instantanée'::character varying, 'demande de réservation'::character varying])::text[]))),
    CONSTRAINT annonces_statut_check CHECK (((statut)::text = ANY ((ARRAY['En cours de vérification'::character varying, 'Publié'::character varying, 'Suspendu'::character varying, 'Désactivé'::character varying, 'Rejeté'::character varying])::text[])))
);


ALTER TABLE public.annonces OWNER TO postgres;

--
-- Name: calendriers; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.calendriers (
    id_calendrier uuid NOT NULL,
    id_annonce uuid NOT NULL,
    date_debut date NOT NULL,
    date_fin date NOT NULL,
    est_disponible boolean NOT NULL,
    type_blockage character varying(255) NOT NULL,
    CONSTRAINT calendriers_type_blockage_check CHECK (((type_blockage)::text = ANY ((ARRAY['Disponible'::character varying, 'Bloque Reservation'::character varying, 'Bloque Manuel'::character varying])::text[])))
);


ALTER TABLE public.calendriers OWNER TO postgres;

--
-- Name: categorie_geographiques; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.categorie_geographiques (
    id_categorie uuid NOT NULL,
    ville character varying(255) NOT NULL,
    region character varying(255) NOT NULL,
    pays character varying(255) NOT NULL
);


ALTER TABLE public.categorie_geographiques OWNER TO postgres;

--
-- Name: evaluations; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.evaluations (
    id_evaluation uuid NOT NULL,
    id_reservation uuid NOT NULL,
    id_auteur uuid NOT NULL,
    id_cible uuid NOT NULL,
    id_annonce uuid,
    type_auteur character varying(255) NOT NULL,
    note numeric(3,2) NOT NULL,
    commentaire text NOT NULL,
    est_signale boolean DEFAULT false NOT NULL,
    motif_signalement character varying(255),
    date_creation timestamp(0) without time zone NOT NULL,
    date_modification timestamp(0) without time zone,
    CONSTRAINT evaluations_type_auteur_check CHECK (((type_auteur)::text = ANY ((ARRAY['voyageur'::character varying, 'hote'::character varying])::text[])))
);


ALTER TABLE public.evaluations OWNER TO postgres;

--
-- Name: minuteries; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.minuteries (
    id_minuterie uuid NOT NULL,
    id_reservation uuid,
    type_minuterie character varying(255) NOT NULL,
    date_echeance timestamp(0) without time zone NOT NULL,
    est_active boolean NOT NULL,
    CONSTRAINT minuteries_type_minuterie_check CHECK (((type_minuterie)::text = ANY ((ARRAY['expiration_demande'::character varying, 'autre'::character varying])::text[])))
);


ALTER TABLE public.minuteries OWNER TO postgres;

--
-- Name: note_detaillees; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.note_detaillees (
    id_note uuid NOT NULL,
    id_evaluation uuid NOT NULL,
    proprete numeric(3,2) NOT NULL,
    communication numeric(3,2) NOT NULL,
    emplacement numeric(3,2) NOT NULL,
    rapport_qualite_prix numeric(3,2) NOT NULL,
    exactitude numeric(3,2) NOT NULL
);


ALTER TABLE public.note_detaillees OWNER TO postgres;

--
-- Name: notifications; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.notifications (
    id_notification uuid NOT NULL,
    id_utilisateur uuid NOT NULL,
    titre character varying(255) NOT NULL,
    contenu text NOT NULL,
    type_alerte character varying(255) NOT NULL,
    est_lue boolean DEFAULT false NOT NULL,
    date_creation timestamp(0) without time zone NOT NULL,
    CONSTRAINT notifications_type_alerte_check CHECK (((type_alerte)::text = ANY ((ARRAY['reservation'::character varying, 'paiement'::character varying, 'avis'::character varying, 'rappel'::character varying, 'systeme'::character varying])::text[])))
);


ALTER TABLE public.notifications OWNER TO postgres;

--
-- Name: paiements; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.paiements (
    id_paiement uuid NOT NULL,
    id_reservation uuid NOT NULL,
    id_voyageur uuid NOT NULL,
    montant numeric(10,2) NOT NULL,
    date_transaction timestamp(0) without time zone NOT NULL,
    methode_paiement character varying(255) NOT NULL,
    statut character varying(255) NOT NULL,
    CONSTRAINT paiements_methode_paiement_check CHECK (((methode_paiement)::text = ANY ((ARRAY['carte_bancaire'::character varying, 'paypal'::character varying, 'autre'::character varying])::text[]))),
    CONSTRAINT paiements_statut_check CHECK (((statut)::text = ANY ((ARRAY['en_attente'::character varying, 'reussi'::character varying, 'echoue'::character varying, 'rembourse'::character varying])::text[])))
);


ALTER TABLE public.paiements OWNER TO postgres;

--
-- Name: politique_annulations; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.politique_annulations (
    id_politique uuid NOT NULL,
    type_politique character varying(255) NOT NULL,
    delai_remb_total smallint NOT NULL,
    delai_remb_partiel smallint NOT NULL,
    taux_remb_partiel numeric(5,2) NOT NULL,
    description text NOT NULL,
    CONSTRAINT politique_annulations_type_politique_check CHECK (((type_politique)::text = ANY ((ARRAY['flexible'::character varying, 'modérée'::character varying, 'stricte'::character varying])::text[])))
);


ALTER TABLE public.politique_annulations OWNER TO postgres;

--
-- Name: profils; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.profils (
    id_profil uuid NOT NULL,
    id_utilisateur uuid NOT NULL,
    photo_url character varying(255),
    description text,
    note_moyenne double precision DEFAULT '0'::double precision NOT NULL
);


ALTER TABLE public.profils OWNER TO postgres;

--
-- Name: recapitulatif_reservations; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.recapitulatif_reservations (
    id_recapitulatif uuid NOT NULL,
    id_reservation uuid NOT NULL,
    details_sejour text NOT NULL,
    montant_total numeric(10,2) NOT NULL,
    frais_service numeric(10,2) NOT NULL,
    montant_hote numeric(10,2) NOT NULL,
    coordonnees_voyageur text NOT NULL,
    coordonnees_hote text NOT NULL,
    date_generation timestamp(0) without time zone NOT NULL
);


ALTER TABLE public.recapitulatif_reservations OWNER TO postgres;

--
-- Name: recus; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.recus (
    id_recu uuid NOT NULL,
    id_paiement uuid NOT NULL,
    numero_facture character varying(255) NOT NULL,
    chemin_pdf character varying(255) NOT NULL,
    date_generation timestamp(0) without time zone NOT NULL
);


ALTER TABLE public.recus OWNER TO postgres;

--
-- Name: remboursements; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.remboursements (
    id_remboursement uuid NOT NULL,
    id_reservation uuid NOT NULL,
    id_voyageur uuid NOT NULL,
    montant numeric(10,2) NOT NULL,
    date_remboursement timestamp(0) without time zone NOT NULL,
    motif character varying(255) NOT NULL,
    CONSTRAINT remboursements_motif_check CHECK (((motif)::text = ANY ((ARRAY['annulation_voyageur'::character varying, 'annulation_hote'::character varying, 'expiration_demande'::character varying])::text[])))
);


ALTER TABLE public.remboursements OWNER TO postgres;

--
-- Name: reservations; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.reservations (
    id_reservation uuid NOT NULL,
    id_annonce uuid NOT NULL,
    id_voyageur uuid NOT NULL,
    id_hote uuid NOT NULL,
    id_politique uuid,
    date_arrivee date NOT NULL,
    date_depart date NOT NULL,
    nb_voyageurs smallint NOT NULL,
    statut character varying(255) NOT NULL,
    mode_reservation character varying(255) NOT NULL,
    montant_total numeric(10,2) NOT NULL,
    frais_service numeric(10,2) NOT NULL,
    message_optionnel text,
    acteur_annulation character varying(255),
    date_creation timestamp(0) without time zone NOT NULL,
    date_modification timestamp(0) without time zone,
    motif_refus text,
    CONSTRAINT reservations_acteur_annulation_check CHECK (((acteur_annulation)::text = ANY ((ARRAY['voyageur'::character varying, 'hote'::character varying, 'systeme'::character varying])::text[]))),
    CONSTRAINT reservations_mode_reservation_check CHECK (((mode_reservation)::text = ANY ((ARRAY['réservation instantanée'::character varying, 'demande de réservation'::character varying])::text[]))),
    CONSTRAINT reservations_statut_check CHECK (((statut)::text = ANY ((ARRAY['En attente'::character varying, 'Confirmée'::character varying, 'En cours'::character varying, 'Terminée'::character varying, 'Annulée'::character varying, 'Refusée'::character varying, 'Expirée'::character varying])::text[])))
);


ALTER TABLE public.reservations OWNER TO postgres;

--
-- Name: jeton_acces; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.jeton_acces (
    id_jeton uuid NOT NULL,
    id_utilisateur uuid NOT NULL,
    token character varying(255) NOT NULL,
    nom_appareil character varying(255),
    date_creation timestamp(0) without time zone NOT NULL,
    date_expiration timestamp(0) without time zone
);


ALTER TABLE public.jeton_acces OWNER TO postgres;

--
-- Name: ticket_litiges; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.ticket_litiges (
    id_ticket uuid NOT NULL,
    id_evaluation uuid NOT NULL,
    id_declarant uuid NOT NULL,
    id_admin uuid NOT NULL,
    motif character varying(255) NOT NULL,
    statut public.enum_statut_litige NOT NULL,
    date_creation timestamp(0) without time zone NOT NULL,
    date_cloture timestamp(0) without time zone
);


ALTER TABLE public.ticket_litiges OWNER TO postgres;

--
-- Name: utilisateurs; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.utilisateurs (
    id_utilisateur uuid NOT NULL,
    nom character varying(255) NOT NULL,
    prenom character varying(255) NOT NULL,
    email character varying(255) NOT NULL,
    mot_de_passe character varying(255) NOT NULL,
    telephone character varying(255) NOT NULL,
    est_hote boolean NOT NULL,
    est_voyageur boolean NOT NULL,
    date_creation timestamp(0) without time zone NOT NULL
);


ALTER TABLE public.utilisateurs OWNER TO postgres;

--
-- Name: verification_identites; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.verification_identites (
    id_verification uuid NOT NULL,
    id_utilisateur uuid NOT NULL,
    type_piece character varying(255) NOT NULL,
    statut public.enum_statut_verif NOT NULL,
    date_soumission timestamp(0) without time zone NOT NULL,
    motif_rejet character varying(255),
    chemin_document character varying(255)
);


ALTER TABLE public.verification_identites OWNER TO postgres;

--
-- Name: versements; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.versements (
    id_versement uuid NOT NULL,
    id_reservation uuid NOT NULL,
    id_hote uuid NOT NULL,
    montant numeric(10,2) NOT NULL,
    date_versement timestamp(0) without time zone NOT NULL,
    reference_bancaire character varying(255) NOT NULL,
    statut character varying(255) NOT NULL,
    CONSTRAINT versements_statut_check CHECK (((statut)::text = ANY ((ARRAY['en_attente'::character varying, 'traite'::character varying, 'echoue'::character varying])::text[])))
);


ALTER TABLE public.versements OWNER TO postgres;

--
-- Name: administrateurs administrateurs_email_unique; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.administrateurs
    ADD CONSTRAINT administrateurs_email_unique UNIQUE (email);


--
-- Name: administrateurs administrateurs_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.administrateurs
    ADD CONSTRAINT administrateurs_pkey PRIMARY KEY (id_admin);


--
-- Name: annonces annonces_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.annonces
    ADD CONSTRAINT annonces_pkey PRIMARY KEY (id_annonce);


--
-- Name: calendriers calendriers_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.calendriers
    ADD CONSTRAINT calendriers_pkey PRIMARY KEY (id_calendrier);


--
-- Name: categorie_geographiques categorie_geographiques_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.categorie_geographiques
    ADD CONSTRAINT categorie_geographiques_pkey PRIMARY KEY (id_categorie);


--
-- Name: evaluations evaluations_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.evaluations
    ADD CONSTRAINT evaluations_pkey PRIMARY KEY (id_evaluation);


--
-- Name: minuteries minuteries_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.minuteries
    ADD CONSTRAINT minuteries_pkey PRIMARY KEY (id_minuterie);


--
-- Name: note_detaillees note_detaillees_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.note_detaillees
    ADD CONSTRAINT note_detaillees_pkey PRIMARY KEY (id_note);


--
-- Name: notifications notifications_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.notifications
    ADD CONSTRAINT notifications_pkey PRIMARY KEY (id_notification);


--
-- Name: paiements paiements_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.paiements
    ADD CONSTRAINT paiements_pkey PRIMARY KEY (id_paiement);


--
-- Name: politique_annulations politique_annulations_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.politique_annulations
    ADD CONSTRAINT politique_annulations_pkey PRIMARY KEY (id_politique);


--
-- Name: profils profils_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.profils
    ADD CONSTRAINT profils_pkey PRIMARY KEY (id_profil);


--
-- Name: recapitulatif_reservations recapitulatif_reservations_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.recapitulatif_reservations
    ADD CONSTRAINT recapitulatif_reservations_pkey PRIMARY KEY (id_recapitulatif);


--
-- Name: recus recus_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.recus
    ADD CONSTRAINT recus_pkey PRIMARY KEY (id_recu);


--
-- Name: remboursements remboursements_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.remboursements
    ADD CONSTRAINT remboursements_pkey PRIMARY KEY (id_remboursement);


--
-- Name: reservations reservations_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.reservations
    ADD CONSTRAINT reservations_pkey PRIMARY KEY (id_reservation);


--
-- Name: jeton_acces jeton_acces_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.jeton_acces
    ADD CONSTRAINT jeton_acces_pkey PRIMARY KEY (id_jeton);

--
-- Name: ticket_litiges ticket_litiges_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.ticket_litiges
    ADD CONSTRAINT ticket_litiges_pkey PRIMARY KEY (id_ticket);


--
-- Name: utilisateurs utilisateurs_email_unique; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.utilisateurs
    ADD CONSTRAINT utilisateurs_email_unique UNIQUE (email);


--
-- Name: utilisateurs utilisateurs_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.utilisateurs
    ADD CONSTRAINT utilisateurs_pkey PRIMARY KEY (id_utilisateur);


--
-- Name: verification_identites verification_identites_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.verification_identites
    ADD CONSTRAINT verification_identites_pkey PRIMARY KEY (id_verification);


--
-- Name: versements versements_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.versements
    ADD CONSTRAINT versements_pkey PRIMARY KEY (id_versement);


--
-- Name: administrateurs administrateurs_id_utilisateur_foreign; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.administrateurs
    ADD CONSTRAINT administrateurs_id_utilisateur_foreign FOREIGN KEY (id_utilisateur) REFERENCES public.utilisateurs(id_utilisateur) ON DELETE CASCADE;


--
-- Name: annonces annonces_id_categorie_foreign; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.annonces
    ADD CONSTRAINT annonces_id_categorie_foreign FOREIGN KEY (id_categorie) REFERENCES public.categorie_geographiques(id_categorie) ON DELETE CASCADE;


--
-- Name: annonces annonces_id_hote_foreign; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.annonces
    ADD CONSTRAINT annonces_id_hote_foreign FOREIGN KEY (id_hote) REFERENCES public.utilisateurs(id_utilisateur) ON DELETE CASCADE;


--
-- Name: annonces annonces_id_politique_foreign; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.annonces
    ADD CONSTRAINT annonces_id_politique_foreign FOREIGN KEY (id_politique) REFERENCES public.politique_annulations(id_politique) ON DELETE SET NULL;


--
-- Name: calendriers calendriers_id_annonce_foreign; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.calendriers
    ADD CONSTRAINT calendriers_id_annonce_foreign FOREIGN KEY (id_annonce) REFERENCES public.annonces(id_annonce) ON DELETE CASCADE;


--
-- Name: evaluations evaluations_id_annonce_foreign; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.evaluations
    ADD CONSTRAINT evaluations_id_annonce_foreign FOREIGN KEY (id_annonce) REFERENCES public.annonces(id_annonce) ON DELETE SET NULL;


--
-- Name: evaluations evaluations_id_auteur_foreign; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.evaluations
    ADD CONSTRAINT evaluations_id_auteur_foreign FOREIGN KEY (id_auteur) REFERENCES public.utilisateurs(id_utilisateur) ON DELETE CASCADE;


--
-- Name: evaluations evaluations_id_cible_foreign; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.evaluations
    ADD CONSTRAINT evaluations_id_cible_foreign FOREIGN KEY (id_cible) REFERENCES public.utilisateurs(id_utilisateur) ON DELETE CASCADE;


--
-- Name: evaluations evaluations_id_reservation_foreign; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.evaluations
    ADD CONSTRAINT evaluations_id_reservation_foreign FOREIGN KEY (id_reservation) REFERENCES public.reservations(id_reservation) ON DELETE CASCADE;


--
-- Name: minuteries minuteries_id_reservation_foreign; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.minuteries
    ADD CONSTRAINT minuteries_id_reservation_foreign FOREIGN KEY (id_reservation) REFERENCES public.reservations(id_reservation) ON DELETE CASCADE;


--
-- Name: note_detaillees note_detaillees_id_evaluation_foreign; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.note_detaillees
    ADD CONSTRAINT note_detaillees_id_evaluation_foreign FOREIGN KEY (id_evaluation) REFERENCES public.evaluations(id_evaluation) ON DELETE CASCADE;


--
-- Name: notifications notifications_id_utilisateur_foreign; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.notifications
    ADD CONSTRAINT notifications_id_utilisateur_foreign FOREIGN KEY (id_utilisateur) REFERENCES public.utilisateurs(id_utilisateur) ON DELETE CASCADE;


--
-- Name: paiements paiements_id_reservation_foreign; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.paiements
    ADD CONSTRAINT paiements_id_reservation_foreign FOREIGN KEY (id_reservation) REFERENCES public.reservations(id_reservation) ON DELETE CASCADE;


--
-- Name: paiements paiements_id_voyageur_foreign; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.paiements
    ADD CONSTRAINT paiements_id_voyageur_foreign FOREIGN KEY (id_voyageur) REFERENCES public.utilisateurs(id_utilisateur) ON DELETE CASCADE;


--
-- Name: profils profils_id_utilisateur_foreign; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.profils
    ADD CONSTRAINT profils_id_utilisateur_foreign FOREIGN KEY (id_utilisateur) REFERENCES public.utilisateurs(id_utilisateur) ON DELETE CASCADE;


--
-- Name: recapitulatif_reservations recapitulatif_reservations_id_reservation_foreign; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.recapitulatif_reservations
    ADD CONSTRAINT recapitulatif_reservations_id_reservation_foreign FOREIGN KEY (id_reservation) REFERENCES public.reservations(id_reservation) ON DELETE CASCADE;


--
-- Name: recus recus_id_paiement_foreign; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.recus
    ADD CONSTRAINT recus_id_paiement_foreign FOREIGN KEY (id_paiement) REFERENCES public.paiements(id_paiement) ON DELETE CASCADE;


--
-- Name: remboursements remboursements_id_reservation_foreign; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.remboursements
    ADD CONSTRAINT remboursements_id_reservation_foreign FOREIGN KEY (id_reservation) REFERENCES public.reservations(id_reservation) ON DELETE CASCADE;


--
-- Name: remboursements remboursements_id_voyageur_foreign; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.remboursements
    ADD CONSTRAINT remboursements_id_voyageur_foreign FOREIGN KEY (id_voyageur) REFERENCES public.utilisateurs(id_utilisateur) ON DELETE CASCADE;


--
-- Name: reservations reservations_id_annonce_foreign; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.reservations
    ADD CONSTRAINT reservations_id_annonce_foreign FOREIGN KEY (id_annonce) REFERENCES public.annonces(id_annonce) ON DELETE CASCADE;


--
-- Name: reservations reservations_id_hote_foreign; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.reservations
    ADD CONSTRAINT reservations_id_hote_foreign FOREIGN KEY (id_hote) REFERENCES public.utilisateurs(id_utilisateur) ON DELETE CASCADE;


--
-- Name: reservations reservations_id_politique_foreign; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.reservations
    ADD CONSTRAINT reservations_id_politique_foreign FOREIGN KEY (id_politique) REFERENCES public.politique_annulations(id_politique) ON DELETE SET NULL;


--
-- Name: reservations reservations_id_voyageur_foreign; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.reservations
    ADD CONSTRAINT reservations_id_voyageur_foreign FOREIGN KEY (id_voyageur) REFERENCES public.utilisateurs(id_utilisateur) ON DELETE CASCADE;


--
-- Name: jeton_acces jeton_acces_id_utilisateur_foreign; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.jeton_acces
    ADD CONSTRAINT jeton_acces_id_utilisateur_foreign FOREIGN KEY (id_utilisateur) REFERENCES public.utilisateurs(id_utilisateur) ON DELETE CASCADE;

--
-- Name: ticket_litiges ticket_litiges_id_admin_foreign; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.ticket_litiges
    ADD CONSTRAINT ticket_litiges_id_admin_foreign FOREIGN KEY (id_admin) REFERENCES public.administrateurs(id_admin) ON DELETE CASCADE;


--
-- Name: ticket_litiges ticket_litiges_id_declarant_foreign; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.ticket_litiges
    ADD CONSTRAINT ticket_litiges_id_declarant_foreign FOREIGN KEY (id_declarant) REFERENCES public.utilisateurs(id_utilisateur) ON DELETE CASCADE;


--
-- Name: ticket_litiges ticket_litiges_id_evaluation_foreign; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.ticket_litiges
    ADD CONSTRAINT ticket_litiges_id_evaluation_foreign FOREIGN KEY (id_evaluation) REFERENCES public.evaluations(id_evaluation) ON DELETE CASCADE;


--
-- Name: verification_identites verification_identites_id_utilisateur_foreign; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.verification_identites
    ADD CONSTRAINT verification_identites_id_utilisateur_foreign FOREIGN KEY (id_utilisateur) REFERENCES public.utilisateurs(id_utilisateur) ON DELETE CASCADE;


--
-- Name: versements versements_id_hote_foreign; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.versements
    ADD CONSTRAINT versements_id_hote_foreign FOREIGN KEY (id_hote) REFERENCES public.utilisateurs(id_utilisateur) ON DELETE CASCADE;


--
-- Name: versements versements_id_reservation_foreign; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.versements
    ADD CONSTRAINT versements_id_reservation_foreign FOREIGN KEY (id_reservation) REFERENCES public.reservations(id_reservation) ON DELETE CASCADE;


--
-- PostgreSQL database dump complete
--


