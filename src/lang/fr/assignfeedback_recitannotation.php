<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * @package assignfeedback_recitannotation
 * @copyright 2025 RECIT
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$string['access_denied'] = 'Accès refusé: Vous n\'avez actuellement pas l\'autorisation d\'utiliser cette fonctionnalité.';
$string['add_edit_annotation'] = 'Ajouter/Modifier une annotation';
$string['add_edit_comment'] = 'Ajouter/Modifier un commentaire';
$string['add_edit_criterion'] = 'Ajouter/Modifier un critère';
$string['add_edit_prompt_ai'] = "Ajouter/Modifier le Prompt à l'IA";
$string['add_new_item'] = 'Ajouter un nouveau item';
$string['ai_api_endpoint'] = 'Point de terminaison API IA';
$string['ai_api_endpoint_desc'] = 'URL complète de l\'API d\'intelligence artificielle à laquelle Moodle doit envoyer les requêtes.';
$string['ai_api_key'] = 'Clé API IA';
$string['ai_api_key_desc'] = 'Clé secrète d\'authentification fournie par le service d\'intelligence artificielle. Elle permet à Moodle d\'accéder de manière sécurisée à l\'API.';
$string['ai_model'] = 'Modèle d\'IA';
$string['ai_model_desc'] = 'Moteur d\'IA utilisé pour analyser le texte et générer des corrections.';
$string['analysis_in_progress'] = 'Analyse en cours';
$string['annotate'] = 'Annoter';
$string['apply'] = "Appliquer";
$string['ask'] = 'Demander';
$string['ask_ai'] = 'Demander à l\'IA';
$string['ask_question'] = 'Poser une question';
$string['back_annotation_view'] = 'Revenir dans l\'écran d\'annotation';
$string['cancel'] = 'Annuler';
$string['cancel_request'] = 'Annuler la requête';
$string['clean_student_production'] = 'Nettoyer le texte de l\'élève';
$string['click_to_filter'] = 'Cliquer pour filtrer';
$string['color'] = 'Couleur';
$string['comment'] = 'Commentaire';
$string['comment_list'] = 'Liste de commentaires';
$string['count'] = 'Nombre';
$string['criteria_list'] = 'Liste de critères';
$string['criterion'] = 'Critère';
$string['default'] = 'Activé par défaut';
$string['default_help'] = 'Si cette option est définie, cette méthode de rétroaction sera activée par défaut pour toutes les nouvelles affectations.';
$string['default_prompt_ai'] = "Tu es un extracteur JSON strict. Analyse le texte et génère un JSON respectant EXACTEMENT ces clés :\n" .
    "1. \"annotatedText\" : texte avec balises [[e1:mot]].\n" .
    "2. \"generalFeedback\" : message d'encouragement.\n" .
    "3. \"corrections\" : tableau d'objets avec EXACTEMENT ces clés :\n" .
    "   - \"id\" : (ex: \"e1\")\n" .
    "   - \"suggestion\" : (le mot corrigé)\n" .
    "   - \"explanation\" : (pourquoi c'est une erreur)\n" .
    "   - \"strategy\" : (astuce pour l'élève)\n" .
    "   - \"criterion\" : (l'ID du critère)\n" .
    "4. Tu dois d'abord identifier les erreurs dans le texte et les marquer avec [[eX:mot]].\n" .
    "5. Chaque balise [[eX:mot]] dans le champ \"annotatedText\" doit avoir une entrée correspondante UNIQUE dans le tableau \"corrections\".\n" .
    "6. L'ID \"eX\" dans le tableau doit correspondre exactement à l'ID visible dans le texte.\n\n" .
    "REMARQUE : N'utilise jamais l'ID (e1, e2) comme nom de clé. Utilise \"id\": \"e1\".\n\n" .
    "CONSIGNE : Retourne UNIQUEMENT l'objet JSON brut. Pas de texte avant, pas de texte après, pas de balises ```json.\n\n" .
    "TONALITÉ : Le champ 'generalFeedback' doit être bienveillant et encourageant.\n\n" .
    "CRITÈRES À UTILISER (ID) :\n" .
    "<<<\n" .
    "PLACEHOLDER_CRITERIA_LIST\n" .
    ">>>";
$string['delete'] = 'Supprimer';
$string['delete_all'] = 'Supprimer tous';
$string['delete_criterion'] = 'Si des commentaires sont associés à ce(s) critère(s), ils seront également supprimés.';
$string['description'] = 'Description';
$string['documentation_download'] = 'Documentation et téléchargement des critères';
$string['edit'] = 'Modifier';
$string['enabled'] = 'Rétroaction par annotation';
$string['enabled_help'] = 'Si cette option est activée, l\'enseignant pourra annoter les devoirs des élèves.';
$string['err_ai_json_format'] = "Erreur de formatage : l'IA n'a pas renvoyé un JSON valide.";
$string['err_ai_refused'] = "L'IA a refusé de traiter cette demande pour des raisons de sécurité ou de politique.";
$string['err_ai_response_too_long'] = 'La réponse est trop longue et a été coupée. Essayez de réduire le texte à corriger.';
$string['err_criterion_not_found'] = 'Le critère "%s" est introuvable.';
$string['err_criterionid_mapping'] = 'Impossible de trouver la correspondance pour le critère lors de la restauration.';
$string['err_no_ai_response'] = "Aucune réponse de l'IA.";
$string['err_xml_parse'] = 'Erreur de lecture du fichier XML : {$a}';
$string['export_criteria'] = 'Exporter des critères';
$string['foreign_key'] = 'Une contrainte de clé étrangère empêche la suppression afin de garantir l\'intégrité des données. Veuillez d\'abord supprimer ou modifier les éléments associés.';
$string['generate_prompt'] = 'Générer le prompt';
$string['import_criteria'] = 'Importer des critères';
$string['input'] = "Entrée";
$string['instruction_ai'] = "Instructions à l'IA";
$string['move_down'] = 'Déplacement vers le bas';
$string['move_up'] = 'Déplacement vers le haut';
$string['msg_action_completed'] = "L'action a été complétée avec succès.";
$string['msg_ai_student_text_prefix'] = "Analyse ce texte d'élève et génère le JSON selon le schéma :\nTexte :\n";
$string['msg_confirm_ai_correction'] = "<p>Cette action demandera à l'IA de corriger le texte, et toutes vos annotations actuelles seront perdues.</p><p><strong>Souhaitez-vous continuer?</strong></p>";
$string['msg_confirm_clean_html_code'] = "Souhaitez-vous vraiment nettoyer le code HTML ?<br/><br/>Cette action supprimera également toutes les annotations que vous avez ajoutées.";
$string['msg_confirm_deletion'] = 'Confirmez-vous la suppression? Cette opération est irréversible.';
$string['msg_confirm_reset_annotation'] = "<p><strong>Souhaitez-vous vraiment réinitialiser l'annotation ?</strong></p><p>Cette action supprimera toutes les annotations que vous avez ajoutées.</p>";
$string['msg_error_highlighting'] = 'Erreur lors de l\'application du surlignage: il y a des nœuds partiellement sélectionnés.';
$string['msg_required_field'] = "Veuillez remplir le champ '%s' avant de continuer.";
$string['name'] = 'Nom';
$string['no_data'] = 'Cette page ne contient actuellement aucune donnée à afficher.';
$string['occurrences'] = 'Occurrences';
$string['ok'] = 'Oui';
$string['only_lowercase'] = 'Veuillez saisir uniquement des lettres minuscules sans espaces.';
$string['output'] = "Sortie";
$string['pluginname'] = 'RÉCIT Rétroaction par annotation';
$string['pluginname2'] = 'Annotation de texte';
$string['print_comment_list'] = 'Imprimer la liste des commentaires';
$string['printed_on'] = "Imprimé le";
$string['privacy:metadata:assignfeedback_recitannotation'] = 'Rétroaction annotée rédigée par un enseignant sur la remise d\'un élève.';
$string['privacy:metadata:assignfeedback_recitannotation:annotation'] = 'Le contenu HTML annoté de la remise de l\'élève.';
$string['privacy:metadata:assignfeedback_recitannotation:lastupdate'] = 'L\'horodatage de la dernière modification apportée à l\'annotation.';
$string['privacy:metadata:assignfeedback_recitannotation:ownerid'] = 'L\'identifiant de l\'enseignant qui a rédigé l\'annotation.';
$string['privacy:metadata:assignfeedback_recitannotation:submission'] = 'L\'identifiant de la remise à laquelle cette annotation est associée.';
$string['prompt'] = "Prompt";
$string['prompt_ai'] = 'Prompt IA';
$string['prompt_ai_help'] = 'Veuillez utiliser les variables suivantes pour le remplacement automatique lors de la création du prompt : PLACEHOLDER_CRITERIA_LIST';
$string['quick_annotation_method'] = 'Méthode rapide';
$string['redo'] = 'Refaire';
$string['reset_annotation'] = "Réinitialiser l'annotation";
$string['result'] = "Résultat";
$string['review_prompt'] = 'Réviser le prompt';
$string['save'] = 'Enregistrer';
$string['schema_annotated_text_desc'] = "Le texte complet où chaque erreur est entourée ainsi : [[id:mot_fautif]]. Il est crucial de laisser la faute de l'élève entre les crochets. Exemple : 'Il a [[e1:manjé]]' (et non 'mangé'). Il est également crucial de retourner le texte de l'élève avec les balises HTML d'origine.";
$string['schema_criterion_desc'] = "L'identificateur du critère. La liste de critères sera passée dans le prompt. Chaque critère aura dans sa description (ID=) qui sera l'identificateur à ajouter dans ce champ.";
$string['schema_explanation_desc'] = 'Explication courte';
$string['schema_general_feedback_desc'] = 'Un conseil global encourageant';
$string['schema_strategy_desc'] = 'Astuce pour retenir';
$string['search_comment'] = 'Cherchez un commentaire';
$string['select_criteria'] = 'Sélectionnez vos critères';
$string['select_item'] = 'Sélectionnez un item';
$string['student_production'] = 'Production de l\'élève';
$string['student_work_placeholder'] = 'Le travail remis par l\'élève s\'affichera ici.';
$string['time_elapsed'] = 'Temps écoulé';
$string['undo'] = 'Défaire';
$string['url_documentation'] = 'URL de la documentation';
$string['url_documentation_desc'] = 'Ce lien permettra aux utilisateurs d\'accéder facilement aux informations ou instructions complémentaires.';
