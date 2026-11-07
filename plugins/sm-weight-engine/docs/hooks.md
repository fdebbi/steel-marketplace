# Points d'intégration

| Élément | Usage |
| --- | --- |
| `SM\Core\Modules::service( 'weight' )` | Service : `families()`, `sections( $family )`, `section( $id )`, `calculate( $input )`, `drawing( $shape, $dims, $title )`, `version()`. `null` si l'extension est inactive (les annonces passent alors en saisie manuelle du poids). |
| `sm_weight_calculate( $input )` | Raccourci du calcul (`array` ou `WP_Error`). |
| `sm_weight_setting( $key )` | Réglage : `density`, `bar_lengths`, `default_length`, `max_length`, `max_bars`, `max_total_kg`, `max_dimension`, `max_thickness`, `max_plate_area`. |
| Filtres thème | `sm_theme_data_weight_families`, `…_weight_family` (`code`), `…_weight_section` (`id`), `…_weight_calc` (entrée du calcul), `…_weight_drawing` (`family`, `dims`), `…_weight_settings`. |
| Capacités | Lecture de l'administration : `sm_moderate` ; modifications : `sm_manage_settings`. |
| Tables | `{prefix}sm_steel_families`, `{prefix}sm_steel_sections`, `{prefix}sm_steel_log`. |

Une annonce (Phase 4) doit enregistrer `section_id`, `engine` et `reference` du résultat, pour pouvoir retracer et recalculer un poids si une donnée de référence est corrigée.
