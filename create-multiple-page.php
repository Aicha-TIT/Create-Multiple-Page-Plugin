<?php
/*
Plugin Name: Create Multiple Page
Description: Plugin permettant de générer plusieurs pages à partir d'un formulaire comprenant les champs titre, description, extrait, ainsi qu'une liste des villes françaises sous forme d'une sélection multiple.
Version: 1.0
Author: Aicha Wannes
*/

function add_plugin_styles() {
    // Récupérer l'URL du fichier CSS
    $css_url = plugin_dir_url( __FILE__ ) . 'css/style.css';
    // Enregistrer le fichier CSS
    wp_enqueue_style( 'plugin-style', $css_url );
}
add_action( 'wp_enqueue_scripts', 'add_plugin_styles' );

function add_bootstrap_to_plugin() {
    // Inclure le fichier CSS de Bootstrap depuis le CDN
    wp_enqueue_style( 'bootstrap-css', 'https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css' );

    // Inclure le fichier JavaScript de Bootstrap depuis le CDN (si nécessaire)
    // wp_enqueue_script( 'bootstrap-js', 'https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js', array('jquery'), null, true );
}

// Action pour ajouter Bootstrap à votre plugin
add_action( 'wp_enqueue_scripts', 'add_bootstrap_to_plugin' );

function create_multiple_page_form_shortcode() {
    ob_start();
    ?>
    <div class="create-multiple-page-form container">
        <form action="<?php echo esc_url( admin_url('admin-post.php') ); ?>" method="post">
            <input type="hidden" name="action" value="create_multiple_page_action">
            <label for="page_title">Titre :</label>
            <input type="text" name="page_title" id="page_title"><br>
            <label for="page_description">Description :</label>
            <textarea name="page_description" id="page_description"></textarea><br>
            <label for="page_excerpt">Extrait :</label>
            <textarea name="page_excerpt" id="page_excerpt"></textarea><br>
            <label for="page_cities">Villes :</label>
            <select name="page_cities[]" id="page_cities" multiple>
                <?php
                // Génération de la liste des villes françaises (Paris 1, Paris 2, ...)
                for ($i = 1; $i <= 20; $i++) {
                    echo '<option value="Paris ' . $i . '">Paris ' . $i . '</option>';
                }
                ?>
            </select><br>
            <input type="submit" class="btn btn-primary" value="Créer la page">
        </form>
    </div>
    <?php

    // Afficher la liste des pages créées dans un carousel
    $pages = get_pages();
    $chunks = array_chunk($pages, 4); // Diviser les pages en groupes de 4
    ?>
    <div id="pageCarousel" class="carousel slide" data-ride="carousel">
        <div class="carousel-inner">
            <?php
            $active = true;
            foreach ($chunks as $chunk) {
                ?>
                <div class="carousel-item <?php echo $active ? 'active' : ''; ?>">
                    <div class="row">
                        <?php
                        foreach ($chunk as $page) {
                            $city_code = get_post_meta($page->ID, 'city_associated', true);
                            ?>
                            <div class="col-md-3">
                                <div class="page-item bg-green">
                                    <h3><?php echo $page->post_title; ?></h3>
                                    <p><?php echo $city_code; ?></p>
                                </div>
                            </div>
                            <?php
                        }
                        ?>
                    </div>
                </div>
                <?php
                $active = false;
            }
            ?>
        </div>
        <a class="carousel-control-prev" href="#pageCarousel" role="button" data-slide="prev">
            <span class="carousel-control-prev-icon" aria-hidden="true"></span>
            <span class="sr-only">Previous</span>
        </a>
        <a class="carousel-control-next" href="#pageCarousel" role="button" data-slide="next">
            <span class="carousel-control-next-icon" aria-hidden="true"></span>
            <span class="sr-only">Next</span>
        </a>
    </div>
    <?php
    return ob_get_clean();
}
add_shortcode( 'create_multiple_page_form', 'create_multiple_page_form_shortcode' );

// Fonction pour gérer la création de la page
function create_multiple_page_action() {
    // Vérifier les autorisations
    if ( ! current_user_can( 'manage_options' ) ) {
        return;
    }

    // Vérifier si le formulaire est soumis et si toutes les données nécessaires sont présentes
    if ( isset( $_POST['action'] ) && $_POST['action'] === 'create_multiple_page_action' && isset( $_POST['page_title'] ) && isset( $_POST['page_description'] ) && isset( $_POST['page_excerpt'] ) && isset( $_POST['page_cities'] ) ) {
        // Créer une nouvelle page pour chaque ville sélectionnée
        foreach ( $_POST['page_cities'] as $city ) {
            // Préparer les données de la nouvelle page
            $new_page_args = array(
                'post_title'    => sanitize_text_field( $_POST['page_title'] ) . ' - ' . sanitize_text_field( $city ),
                'post_content'  => wp_kses_post( $_POST['page_description'] ),
                'post_excerpt'  => wp_kses_post( $_POST['page_excerpt'] ),
                'post_status'   => 'publish',
                'post_type'     => 'page',
            );

            // Insérer la nouvelle page dans la base de données
            $new_page_id = wp_insert_post( $new_page_args );

            // Ajouter une métadonnée pour indiquer la ville associée à la page
            if ( $new_page_id ) {
                add_post_meta( $new_page_id, 'city_associated', $city );
            }
        }
    }

    // Rediriger vers la page d'accueil après la création des pages
    wp_redirect( home_url() );
    exit;
}
add_action( 'admin_post_create_multiple_page_action', 'create_multiple_page_action' );
add_action( 'admin_post_nopriv_create_multiple_page_action', 'create_multiple_page_action' );
