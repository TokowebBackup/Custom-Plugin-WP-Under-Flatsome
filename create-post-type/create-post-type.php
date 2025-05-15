<?php

/**
 * Plugin Name: Custom File Uploader
 * Description: Plugin untuk upload file dokumen dan menampilkannya via shortcode.
 * Version: 1.0
 * Author: Puji Ermanto<dev@codesyariah.co.id> | Aka Mamam Yuk | Aka Janji mas joni
 */

if (! defined('ABSPATH')) exit;

function cfu_post_edit_form_tag()
{
    echo ' enctype="multipart/form-data"';
}
add_action('post_edit_form_tag', 'cfu_post_edit_form_tag');
// Register Custom Post Type
function cfu_register_post_type()
{
    register_post_type('upload_file', [
        'labels' => [
            'name' => 'Upload File',
            'singular_name' => 'File',
        ],
        'public' => true,
        'has_archive' => true,
        'supports' => ['title', 'thumbnail'],
        'menu_icon' => 'dashicons-media-document'
    ]);
}
add_action('init', 'cfu_register_post_type');

// Add Meta Box for File Upload
function cfu_add_meta_boxes()
{
    add_meta_box('cfu_file_upload', 'Upload File', 'cfu_file_upload_callback', 'upload_file', 'normal', 'default');
}
add_action('add_meta_boxes', 'cfu_add_meta_boxes');

function cfu_file_upload_callback($post)
{
    $file_url = get_post_meta($post->ID, '_cfu_file', true);
    echo '<input type="file" name="cfu_file_upload" />';
    if ($file_url) {
        echo '<p>Current File: <a href="' . esc_url($file_url) . '" target="_blank">Download</a></p>';
    }
}

// Save the uploaded file
function cfu_save_post($post_id)
{
    if (!empty($_FILES['cfu_file_upload']['name'])) {
        $supported_types = ['application/pdf', 'application/msword', 'application/vnd.ms-excel', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'];

        if (in_array($_FILES['cfu_file_upload']['type'], $supported_types)) {
            $upload = wp_handle_upload($_FILES['cfu_file_upload'], ['test_form' => false]);
            if (isset($upload['url'])) {
                update_post_meta($post_id, '_cfu_file', esc_url_raw($upload['url']));
            }
        }
    }
}
add_action('save_post', 'cfu_save_post');

// Shortcode for Frontend Display
function cfu_frontend_display($atts)
{
    $atts = shortcode_atts(['id' => ''], $atts, 'cfu_file');

    if (!$atts['id']) return 'No file selected.';

    $post = get_post($atts['id']);
    $file_url = get_post_meta($post->ID, '_cfu_file', true);

    if (!$post || !$file_url) return 'File not found.';

    $file_path = str_replace(site_url('/'), ABSPATH, $file_url); // Konversi URL ke path server
    $file_name = basename($file_path);
    $file_size = file_exists($file_path) ? size_format(filesize($file_path), 2) : 'Unknown';
    $file_type = file_exists($file_path) ? mime_content_type($file_path) : 'Unknown';
    $thumbnail = get_the_post_thumbnail_url($post->ID, 'medium');

    ob_start(); ?>
    <div class="cfu-wrapper">
        <div class="cfu-flex-container">
            <div class="cfu-preview">
                <?php if ($thumbnail): ?>
                    <img src="<?php echo esc_url($thumbnail); ?>" alt="<?php echo esc_attr($post->post_title); ?>" style="border: 1px solid #ccc; border-radius: 4px;">
                <?php else: ?>
                    <iframe src="<?php echo esc_url($file_url); ?>" width="100%" height="500px" style="border:1px solid #ccc;"></iframe>
                <?php endif; ?>
            </div>
            <div class="cfu-info">
                <ul class="list-group">
                    <li class="list-group-item"><strong>Judul:</strong> <?php echo esc_html($post->post_title); ?></li>
                    <li class="list-group-item"><strong>Nama File:</strong> <?php echo esc_html($file_name); ?></li>
                    <li class="list-group-item"><strong>Ukuran File:</strong> <?php echo esc_html($file_size); ?></li>
                    <li class="list-group-item"><strong>Tipe File:</strong> <?php echo esc_html($file_type); ?></li>
                    <li class="list-group-item"><strong>Tanggal Upload:</strong> <?php echo get_the_date('', $post); ?></li>
                </ul>
                <a id="download-file" class="btn btn-primary mt-3" href="<?php echo esc_url($file_url); ?>" download style="display: none;">Download File</a>
            </div>
        </div>
    </div>

    <style>
        .cfu-flex-container {
            display: flex;
            flex-wrap: wrap;
            gap: 2rem;
        }

        .cfu-preview,
        .cfu-info {
            flex: 1 1 45%;
        }

        @media (max-width: 768px) {

            .cfu-preview,
            .cfu-info {
                flex: 1 1 100%;
            }
        }

        .cfu-wrapper {
            max-width: 80vw;
            margin: 2rem auto;
            padding: 1rem;
            /* border: 1px solid #ddd; */
            /* border-radius: 8px; */
            background: #fff;
            /* box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05); */
        }

        .cfu-preview {
            margin-bottom: 2rem;
        }

        .cfu-preview iframe {
            display: block;
            max-width: 100%;
            margin: 0 auto;
            border-radius: 4px;
        }

        .cfu-preview img {
            max-width: 100%;
            height: auto;
            border-radius: 4px;
        }

        .cfu-info {
            padding: 0 1rem;
        }

        .list-group {
            padding-left: 0;
            margin-bottom: 1rem;
            list-style: none;
            border: 1px solid #dee2e6;
            border-radius: 0.25rem;
        }

        .list-group-item {
            position: relative;
            display: block;
            padding: 0.75rem 1.25rem;
            background-color: #fff;
            border-bottom: 1px solid #dee2e6;
        }

        .list-group-item:last-child {
            border-bottom: none;
        }

        .btn.btn-primary {
            background-color: #007bff;
            color: #fff;
            padding: 0.5rem 1rem;
            border: none;
            border-radius: 4px;
            text-decoration: none;
        }

        .btn.btn-primary:hover {
            background-color: #0056b3;
        }
    </style>
<?php
    return ob_get_clean();
}


// Enqueue SweetAlert and custom script
function cfu_enqueue_sweetalert_script()
{
?>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const downloadBtn = document.querySelector('.cfu-info a.btn');
            if (downloadBtn) {
                downloadBtn.addEventListener('click', function(e) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Download Dimulai',
                        text: 'File sedang diunduh. Cek folder unduhan Anda.',
                        timer: 5000,
                        showConfirmButton: false
                    });
                });
            }
        });
    </script>
<?php
}
add_action('wp_footer', 'cfu_enqueue_sweetalert_script');


add_shortcode('cfu_file', 'cfu_frontend_display');
