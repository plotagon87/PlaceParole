<?php
/**
 * lang/fr.php
 * All French interface text
 * Every key here MUST match exactly the keys in en.php
 */

return [
    // --- App Name & Navigation ---
    'app_name'              => 'PlaceParole',
    'app_tagline'           => 'Plateforme de Feedback et de Communication du Marché',
    'nav_home'              => 'Accueil',
    'nav_complaints'        => 'Plaintes',
    'nav_suggestions'       => 'Suggestions',
    'nav_community'         => 'Communauté',
    'nav_announcements'     => 'Annonces',
    'nav_dashboard'         => 'Tableau de Bord',
    'nav_profile'           => 'Profil',
    'nav_logout'            => 'Déconnexion',
    'nav_language'          => 'Langue',

    // --- Authentication ---
    'login'                 => 'Connexion',
    'register'              => "S'inscrire",
    'email'                 => 'Adresse Email',
    'password'              => 'Mot de passe',
    'password_confirm'      => 'Confirmer le mot de passe',
    'name'                  => 'Nom complet',
    'phone'                 => 'Numéro de téléphone',
    'stall_number'          => 'Numéro de stand',
    'select_market'         => 'Choisissez votre marché',
    'select_category'       => 'Sélectionnez une Catégorie',
    'register_market'       => 'Enregistrer un nouveau marché',
    'market_name'           => 'Nom du marché',
    'market_location'       => 'Localisation du marché',
    'i_am_a'                => 'Je suis',
    'seller'                => 'Vendeur',
    'manager'               => 'Gestionnaire',
    'already_have_account'  => 'Vous avez déjà un compte ?',
    'login_here'            => 'Connectez-vous ici',
    'no_account'            => "Pas encore de compte ?",
    'register_now'          => "S'inscrire maintenant",
    'login_success'         => 'Connexion réussie ! Bienvenue.',
    'register_success'      => "Inscription réussie ! Vous pouvez maintenant vous connecter.",
    'logout_success'        => 'Vous avez été déconnecté.',

    // Registration page headings/intros
    'seller_registration'          => 'Inscription vendeur',
    'register_seller'              => 'Inscrire en tant que vendeur',
    'seller_registration_intro'    => 'Choisissez votre marché et créez votre profil de vendeur.',

    'manager_registration'         => 'Inscription Gestionnaire de Marché',
    'register_manager_intro'       => 'Créez votre marché et votre compte gestionnaire en une seule étape.',

    // --- Complaints ---
    'submit_complaint'      => 'Soumettre une Plainte',
    'complaint_category'    => 'Catégorie',
    'complaint_description' => 'Décrivez votre plainte',
    'complaint_sent'        => 'Votre plainte a bien été reçue.',
    'your_ref_code'         => 'Votre code de référence est',
    'keep_ref_code'         => 'Veuillez sauvegarder ce code pour suivre votre plainte.',
    'track_complaint'       => 'Suivre une Plainte',
    'enter_ref_code'        => 'Entrez votre code de référence',
    'view_status'           => 'Voir le Statut',
    'cat_infrastructure'    => 'Infrastructure',
    'cat_sanitation'        => 'Assainissement',
    'cat_stall_allocation'  => 'Attribution des stands',
    'cat_security'          => 'Sécurité',
    'cat_other'             => 'Autre',

    // --- Status Labels ---
    'status_pending'        => 'En attente',
    'status_in_review'      => 'En cours d\'examen',
    'status_resolved'       => 'Résolu',

    // --- Suggestions ---
    'submit_suggestion'     => 'Soumettre une Suggestion',
    'suggestion_title'      => 'Titre',
    'suggestion_description'=> 'Décrivez votre idée',
    'suggestion_sent'       => 'Votre suggestion a été soumise.',

    // --- Community ---
    'report_event'          => 'Signaler un Événement Communautaire',
    'event_type'            => 'Type d\'événement',
    'event_death'           => 'Décès',
    'event_illness'         => 'Maladie',
    'event_emergency'       => 'Urgence',
    'event_other'           => 'Autre',
    'person_name'           => 'Nom de la personne affectée',
    'event_description'     => 'Détails',
    'report_sent'           => 'Votre signalement a été partagé avec la communauté.',

    // --- Announcements ---
    'announcements'         => 'Annonces',
    'no_announcements'      => 'Aucune annonce pour le moment.',
    'new_announcement'      => 'Nouvelle Annonce',
    'announcement_title'    => 'Titre',
    'announcement_body'     => 'Message',
    'announcement_picture'  => 'Image',
    'send_via'              => "Envoyer via",
    'broadcast_announcement'=> 'Diffuser une Annonce',
    'announcement_channels' => 'Envoyer via',
    'channel_web'           => 'Web/Dans l\'app',
    'channel_sms'           => 'SMS',
    'channel_email'         => 'Email',
    'channel_gmail'         => 'Gmail',
    'channel_whatsapp'      => 'WhatsApp',
    'error_select_channel'  => 'Veuillez sélectionner au moins un canal.',
    'announcement_sent'     => 'Annonce envoyée avec succès sur tous les canaux.',

    // --- Community Feedback ---
    'submit_feedback'       => 'Partagez Vos Commentaires',
    'feedback_description'  => 'Partagez vos idées, suggestions ou commentaires sur la communauté du marché',
    'feedback_title'        => 'Titre des commentaires',
    'feedback_message'      => 'Vos commentaires',
    'feedback_title_placeholder' => 'Titre bref de vos commentaires',
    'feedback_placeholder'  => 'Partagez vos idées ou préoccupations en détail...',
    'feedback_sent'         => 'Merci ! Vos commentaires ont été reçus et seront examinés par notre équipe.',
    'feedback_anonymous'    => 'Vos commentaires resteront anonymes pour les autres membres du marché.',

    // --- Pending Moderation ---
    'pending_suggestions'   => 'Suggestions en attente',
    'pending_feedback'      => 'Commentaires en attente',
    'approve'               => 'Approuver',
    'reject'                => 'Rejeter',
    'approved'              => 'Approuvé',
    'rejected'              => 'Rejeté',
    'reason'                => 'Raison (optionnel)',
    'optional'              => 'optionnel',
    'approve_success'       => 'Approuvé avec succès',
    'reject_success'        => 'Rejeté avec succès',

    // --- Notifications ---  
    'notifications'         => 'Notifications',
    'no_notifications'      => 'Pas de nouvelles notifications',
    'new_suggestion_notif'  => 'Nouvelle suggestion en attente d\'approbation',
    'new_feedback_notif'    => 'Nouveaux commentaires en attente d\'approbation',
    'new_announcement_notif'=> 'Nouvelle annonce de la gestion',
    'suggestion_approved'   => 'Votre suggestion a été approuvée',
    'feedback_approved'     => 'Vos commentaires ont été approuvés',

    // --- Dashboard (Manager) ---
    'manager_dashboard'     => 'Tableau de Bord du Gestionnaire',
    'analytics_dashboard'   => 'Tableau de Bord Analytique',
    'total_complaints'      => 'Plaintes Totales',
    'pending_complaints'    => 'En attente',
    'resolved_complaints'   => 'Résolues',
    'all_complaints'        => 'Toutes les Plaintes',
    'filter_by_status'      => 'Filtrer par Statut',
    'filter_by_category'    => 'Filtrer par Catégorie',
    'all_categories'        => 'Toutes les Catégories',
    'seller_name'           => 'Nom du Vendeur',
    'date'                  => 'Date',
    'actions'               => 'Actions',
    'view'                  => 'Afficher',
    'respond'               => 'Répondre',
    'mark_resolved'         => 'Marquer comme Résolu',

    // --- Analytics ---
    'by_category'           => 'Plaintes par Catégorie',
    'by_month'              => 'Plaintes par Mois',
    'recent_complaints'     => 'Plaintes Récentes',
    'avg_resolution_time'   => 'Temps Moyen de Résolution',
    'complaints_last_12_months' => '12 Derniers Mois',
    'hours'                 => 'hrs',
    'no_resolved'           => 'Aucune résolu',
    'nav_analytics'         => 'Analytique',

    // --- General ---
    'submit'                => 'Soumettre',
    'save'                  => 'Enregistrer',
    'cancel'                => 'Annuler',
    'back'                  => 'Retour',
    'search'                => 'Rechercher',
    'filter'                => 'Filtrer',
    'all'                   => 'Tous',
    'category'              => 'Catégorie',
    'created_at'            => 'Soumis',
    'updated_at'            => 'Mis à jour',
    'response'              => 'Réponse',
    'error_required'        => 'Ce champ est obligatoire.',
    'error_invalid_email'   => 'Veuillez entrer une adresse email valide.',
    'error_password_match'  => 'Les mots de passe ne correspondent pas.',
    'error_email_exists'    => 'Cet email est déjà enregistré.',
    'error_phone_exists'    => 'Ce numéro de téléphone est déjà enregistré.',
    'error_invalid_login'   => 'Email ou mot de passe invalide.',
    'success'               => 'Succès',
    'error'                 => 'Erreur',
    'close'                 => 'Fermer',
    'loading'               => 'Chargement...',
];
?>
