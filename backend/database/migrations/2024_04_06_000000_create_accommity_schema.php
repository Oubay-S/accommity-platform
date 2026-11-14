<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     * Crée l'ensemble des 20 tables métier d'Accommity (PostgreSQL / Laravel).
     */
    public function up(): void
    {
        // 1. UTILISATEURS
        Schema::create('utilisateurs', function (Blueprint $table) {
            $table->uuid('id_utilisateur')->primary();
            $table->string('nom');
            $table->string('prenom');
            $table->string('email')->unique();
            $table->string('mot_de_passe');
            $table->string('telephone')->nullable();
            $table->boolean('est_hote');
            $table->boolean('est_voyageur');
            $table->boolean('est_actif')->default(true);
            $table->timestamp('date_creation');
        });

        // 2. PROFILS
        Schema::create('profils', function (Blueprint $table) {
            $table->uuid('id_profil')->primary();
            $table->uuid('id_utilisateur')->unique();
            $table->string('photo_url')->nullable();
            $table->text('description')->nullable();
            $table->decimal('note_moyenne', 3, 2)->default(0);

            $table->foreign('id_utilisateur')->references('id_utilisateur')->on('utilisateurs')->onDelete('cascade');
        });

        // 3. ADMINISTRATEURS
        Schema::create('administrateurs', function (Blueprint $table) {
            $table->uuid('id_admin')->primary();
            $table->uuid('id_utilisateur');
            $table->string('email')->unique();
            $table->string('mot_de_passe');
            $table->timestamp('derniere_connexion')->nullable();

            $table->index('id_utilisateur');
            $table->foreign('id_utilisateur')->references('id_utilisateur')->on('utilisateurs')->onDelete('cascade');
        });

        // 4. VERIFICATION_IDENTITES
        Schema::create('verification_identites', function (Blueprint $table) {
            $table->uuid('id_verification')->primary();
            $table->uuid('id_utilisateur');
            $table->string('type_piece');
            $table->enum('statut', ['en_cours', 'validee', 'rejetee'])->default('en_cours');
            $table->timestamp('date_soumission');
            $table->string('motif_rejet')->nullable();
            $table->string('chemin_document')->nullable();
            $table->uuid('id_admin_traitant')->nullable();
            $table->timestamp('date_traitement')->nullable();

            $table->index('id_utilisateur');
            $table->index('id_admin_traitant');
            $table->foreign('id_utilisateur')->references('id_utilisateur')->on('utilisateurs')->onDelete('cascade');
            $table->foreign('id_admin_traitant')->references('id_admin')->on('administrateurs')->nullOnDelete();
        });

        // 5. JETON_ACCES (remplace sessions pour l'architecture API Token / Angular)
        Schema::create('jeton_acces', function (Blueprint $table) {
            $table->uuid('id_jeton')->primary();
            $table->uuid('id_utilisateur');
            $table->string('token');
            $table->string('nom_appareil')->nullable();
            $table->timestamp('date_creation');
            $table->timestamp('date_expiration')->nullable();

            $table->index('id_utilisateur');
            $table->foreign('id_utilisateur')->references('id_utilisateur')->on('utilisateurs')->onDelete('cascade');
        });

        // 6. CATEGORIE_GEOGRAPHIQUES
        Schema::create('categorie_geographiques', function (Blueprint $table) {
            $table->uuid('id_categorie')->primary();
            $table->string('ville');
            $table->string('region');
            $table->string('pays');
        });

        // 7. POLITIQUE_ANNULATIONS
        Schema::create('politique_annulations', function (Blueprint $table) {
            $table->uuid('id_politique')->primary();
            $table->enum('type_politique', ['flexible', 'moderee', 'stricte']);
            $table->smallInteger('delai_remb_total');
            $table->smallInteger('delai_remb_partiel');
            $table->decimal('taux_remb_partiel', 5, 2);
            $table->text('description');
        });

        // 8. ANNONCES
        Schema::create('annonces', function (Blueprint $table) {
            $table->uuid('id_annonce')->primary();
            $table->uuid('id_hote');
            $table->uuid('id_categorie');
            $table->uuid('id_politique');
            $table->string('titre');
            $table->text('description');
            $table->text('photo_url');
            $table->string('type_logement');
            $table->string('adresse');
            $table->smallInteger('capacite');
            $table->decimal('tarif_nuit', 10, 2);
            $table->enum('mode_reservation', ['instantanee', 'sur_demande']);
            $table->enum('statut', ['en_attente', 'publiee', 'suspendue', 'desactivee', 'rejetee'])->default('en_attente');
            $table->text('motif_moderation')->nullable();
            $table->text('equipements');
            $table->text('reglement_interieur')->nullable();
            $table->timestamp('date_creation');

            $table->index('id_hote');
            $table->index('id_categorie');
            $table->index('id_politique');
            $table->foreign('id_hote')->references('id_utilisateur')->on('utilisateurs')->onDelete('cascade');
            $table->foreign('id_categorie')->references('id_categorie')->on('categorie_geographiques')->restrictOnDelete();
            $table->foreign('id_politique')->references('id_politique')->on('politique_annulations')->restrictOnDelete();
        });

        // 9. CALENDRIERS
        Schema::create('calendriers', function (Blueprint $table) {
            $table->uuid('id_calendrier')->primary();
            $table->uuid('id_annonce');
            $table->date('date_debut');
            $table->date('date_fin');
            $table->boolean('est_disponible');
            $table->enum('type_blockage', ['disponible', 'bloque_reservation', 'bloque_manuel']);

            $table->index('id_annonce');
            $table->foreign('id_annonce')->references('id_annonce')->on('annonces')->onDelete('cascade');
        });

        // 10. RESERVATIONS
        Schema::create('reservations', function (Blueprint $table) {
            $table->uuid('id_reservation')->primary();
            $table->uuid('id_annonce');
            $table->uuid('id_voyageur');
            $table->uuid('id_hote');
            $table->uuid('id_politique')->nullable();
            $table->date('date_arrivee');
            $table->date('date_depart');
            $table->smallInteger('nb_voyageurs');
            $table->enum('statut', ['en_attente', 'confirmee', 'en_cours', 'terminee', 'annulee', 'refusee', 'expiree'])->default('en_attente');
            $table->enum('mode_reservation', ['instantanee', 'sur_demande']);
            $table->decimal('montant_total', 10, 2);
            $table->decimal('frais_service', 10, 2);
            $table->text('message_optionnel')->nullable();
            $table->text('motif_refus')->nullable();
            $table->text('motif_annulation')->nullable();
            $table->timestamp('date_annulation')->nullable();
            $table->enum('acteur_annulation', ['voyageur', 'hote', 'systeme'])->nullable();
            $table->timestamp('date_creation');
            $table->timestamp('date_modification')->nullable();

            $table->index('id_annonce');
            $table->index('id_voyageur');
            $table->index('id_hote');
            $table->index('id_politique');
            $table->foreign('id_annonce')->references('id_annonce')->on('annonces')->onDelete('cascade');
            $table->foreign('id_voyageur')->references('id_utilisateur')->on('utilisateurs')->onDelete('cascade');
            $table->foreign('id_hote')->references('id_utilisateur')->on('utilisateurs')->onDelete('cascade');
            $table->foreign('id_politique')->references('id_politique')->on('politique_annulations')->onDelete('set null');
        });

        // 11. MINUTERIES
        Schema::create('minuteries', function (Blueprint $table) {
            $table->uuid('id_minuterie')->primary();
            $table->uuid('id_reservation')->nullable();
            $table->enum('type_minuterie', ['expiration_demande', 'autre']);
            $table->timestamp('date_echeance');
            $table->boolean('est_active');

            $table->index('id_reservation');
            $table->foreign('id_reservation')->references('id_reservation')->on('reservations')->onDelete('cascade');
        });

        // 12. RECAPITULATIF_RESERVATIONS
        Schema::create('recapitulatif_reservations', function (Blueprint $table) {
            $table->uuid('id_recapitulatif')->primary();
            $table->uuid('id_reservation')->unique();
            $table->text('details_sejour');
            $table->decimal('montant_total', 10, 2);
            $table->decimal('frais_service', 10, 2);
            $table->decimal('montant_hote', 10, 2);
            $table->text('coordonnees_voyageur');
            $table->text('coordonnees_hote');
            $table->timestamp('date_generation');

            $table->foreign('id_reservation')->references('id_reservation')->on('reservations')->onDelete('cascade');
        });

        // 13. PAIEMENTS
        Schema::create('paiements', function (Blueprint $table) {
            $table->uuid('id_paiement')->primary();
            $table->uuid('id_reservation')->unique();
            $table->uuid('id_voyageur');
            $table->decimal('montant', 10, 2);
            $table->timestamp('date_transaction');
            $table->enum('methode_paiement', ['carte_bancaire', 'paypal', 'autre']);
            $table->enum('statut', ['en_attente', 'reussi', 'echoue', 'rembourse']);

            $table->index('id_voyageur');
            $table->foreign('id_reservation')->references('id_reservation')->on('reservations')->onDelete('cascade');
            $table->foreign('id_voyageur')->references('id_utilisateur')->on('utilisateurs')->onDelete('cascade');
        });

        // 14. VERSEMENTS
        Schema::create('versements', function (Blueprint $table) {
            $table->uuid('id_versement')->primary();
            $table->uuid('id_reservation');
            $table->uuid('id_hote');
            $table->decimal('montant', 10, 2);
            $table->timestamp('date_versement')->nullable();
            $table->string('reference_bancaire')->nullable();
            $table->timestamp('date_liberation_prevue');
            $table->enum('statut', ['en_attente', 'traite', 'echoue']);

            $table->index('id_reservation');
            $table->index('id_hote');
            $table->foreign('id_reservation')->references('id_reservation')->on('reservations')->onDelete('cascade');
            $table->foreign('id_hote')->references('id_utilisateur')->on('utilisateurs')->onDelete('cascade');
        });

        // 15. REMBOURSEMENTS
        Schema::create('remboursements', function (Blueprint $table) {
            $table->uuid('id_remboursement')->primary();
            $table->uuid('id_reservation');
            $table->uuid('id_voyageur');
            $table->uuid('id_paiement');
            $table->decimal('montant', 10, 2);
            $table->decimal('taux_applique', 5, 2);
            $table->timestamp('date_remboursement');
            $table->enum('motif', ['annulation_voyageur', 'annulation_hote', 'expiration_demande']);

            $table->index('id_reservation');
            $table->index('id_voyageur');
            $table->index('id_paiement');
            $table->foreign('id_reservation')->references('id_reservation')->on('reservations')->onDelete('cascade');
            $table->foreign('id_voyageur')->references('id_utilisateur')->on('utilisateurs')->onDelete('cascade');
            $table->foreign('id_paiement')->references('id_paiement')->on('paiements')->onDelete('cascade');
        });

        // 16. RECUS
        Schema::create('recus', function (Blueprint $table) {
            $table->uuid('id_recu')->primary();
            $table->uuid('id_paiement');
            $table->string('numero_facture');
            $table->string('chemin_pdf')->nullable();
            $table->timestamp('date_generation');

            $table->index('id_paiement');
            $table->foreign('id_paiement')->references('id_paiement')->on('paiements')->onDelete('cascade');
        });

        // 17. EVALUATIONS
        Schema::create('evaluations', function (Blueprint $table) {
            $table->uuid('id_evaluation')->primary();
            $table->uuid('id_reservation');
            $table->uuid('id_auteur');
            $table->uuid('id_cible');
            $table->uuid('id_annonce')->nullable();
            $table->enum('type_auteur', ['voyageur', 'hote']);
            $table->decimal('note', 3, 2);
            $table->text('commentaire');
            $table->boolean('est_signale')->default(false);
            $table->string('motif_signalement')->nullable();
            $table->timestamp('date_creation');
            $table->timestamp('date_modification')->nullable();

            $table->unique(['id_reservation', 'id_auteur']);
            $table->index('id_auteur');
            $table->index('id_cible');
            $table->index('id_annonce');
            $table->foreign('id_reservation')->references('id_reservation')->on('reservations')->onDelete('cascade');
            $table->foreign('id_auteur')->references('id_utilisateur')->on('utilisateurs')->onDelete('cascade');
            $table->foreign('id_cible')->references('id_utilisateur')->on('utilisateurs')->onDelete('cascade');
            $table->foreign('id_annonce')->references('id_annonce')->on('annonces')->onDelete('set null');
        });

        // 18. NOTE_DETAILLEES
        Schema::create('note_detaillees', function (Blueprint $table) {
            $table->uuid('id_note')->primary();
            $table->uuid('id_evaluation');
            $table->decimal('proprete', 3, 2);
            $table->decimal('communication', 3, 2);
            $table->decimal('emplacement', 3, 2);
            $table->decimal('rapport_qualite_prix', 3, 2);
            $table->decimal('exactitude', 3, 2);

            $table->index('id_evaluation');
            $table->foreign('id_evaluation')->references('id_evaluation')->on('evaluations')->onDelete('cascade');
        });

        // 19. TICKET_LITIGES
        Schema::create('ticket_litiges', function (Blueprint $table) {
            $table->uuid('id_ticket')->primary();
            $table->uuid('id_evaluation');
            $table->uuid('id_declarant');
            $table->uuid('id_admin')->nullable();
            $table->uuid('id_reservation')->nullable();
            $table->string('motif');
            $table->enum('statut', ['en_cours', 'cloture'])->default('en_cours');
            $table->timestamp('date_creation');
            $table->timestamp('date_cloture')->nullable();

            $table->index('id_evaluation');
            $table->index('id_declarant');
            $table->index('id_admin');
            $table->index('id_reservation');
            $table->foreign('id_evaluation')->references('id_evaluation')->on('evaluations')->onDelete('cascade');
            $table->foreign('id_declarant')->references('id_utilisateur')->on('utilisateurs')->onDelete('cascade');
            $table->foreign('id_admin')->references('id_admin')->on('administrateurs')->nullOnDelete();
            $table->foreign('id_reservation')->references('id_reservation')->on('reservations')->nullOnDelete();
        });

        // 20. NOTIFICATIONS
        Schema::create('notifications', function (Blueprint $table) {
            $table->uuid('id_notification')->primary();
            $table->uuid('id_utilisateur');
            $table->string('titre');
            $table->text('contenu');
            $table->enum('type_alerte', ['reservation', 'paiement', 'avis', 'rappel', 'systeme']);
            $table->boolean('est_lue')->default(false);
            $table->timestamp('date_creation');

            $table->index('id_utilisateur');
            $table->foreign('id_utilisateur')->references('id_utilisateur')->on('utilisateurs')->onDelete('cascade');
        });

        if (DB::getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE utilisateurs ADD CONSTRAINT utilisateurs_role_check CHECK (est_hote OR est_voyageur)');
            DB::statement('ALTER TABLE evaluations ADD CONSTRAINT evaluations_note_check CHECK (note BETWEEN 1 AND 5)');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notifications');
        Schema::dropIfExists('ticket_litiges');
        Schema::dropIfExists('note_detaillees');
        Schema::dropIfExists('evaluations');
        Schema::dropIfExists('recus');
        Schema::dropIfExists('remboursements');
        Schema::dropIfExists('versements');
        Schema::dropIfExists('paiements');
        Schema::dropIfExists('recapitulatif_reservations');
        Schema::dropIfExists('minuteries');
        Schema::dropIfExists('reservations');
        Schema::dropIfExists('calendriers');
        Schema::dropIfExists('annonces');
        Schema::dropIfExists('politique_annulations');
        Schema::dropIfExists('categorie_geographiques');
        Schema::dropIfExists('jeton_acces');
        Schema::dropIfExists('verification_identites');
        Schema::dropIfExists('administrateurs');
        Schema::dropIfExists('profils');
        Schema::dropIfExists('utilisateurs');
    }
};
