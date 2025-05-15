<?php
/*
Plugin Name: Custom WC Tabs with Location Filter
Description: Menampilkan produk berdasarkan lokasi dengan tab navigasi dan loader.
Version: 1.1
Author: Puji Ermanto
*/

function custom_wc_tabs_shortcode()
{
    ob_start();
?>
    <style>
        /* CSS disini */
        /* (Copy CSS dari contoh sebelumnya) */
        .custom-wc-tabs {
            position: relative;
        }

        .custom-wc-tabs ul.tabs {
            display: flex;
            overflow-x: auto;
            white-space: nowrap;
            border-bottom: 2px solid #eee;
            padding: 0;
            margin: 0 0 20px 0;
            scrollbar-width: thin;
        }

        .custom-wc-tabs ul.tabs::-webkit-scrollbar {
            display: none;
        }

        .custom-wc-tabs ul.tabs li {
            list-style: none;
            padding: 12px 20px;
            cursor: pointer;
            color: #333;
            border-bottom: 2px solid transparent;
            transition: all 0.3s ease;
            flex-shrink: 0;
            font-weight: 500;
        }

        .custom-wc-tabs ul.tabs li {
            position: relative;
            color: #555;
            font-weight: 500;
            cursor: pointer;
            padding: 12px 20px;
            transition: color 0.3s ease;
        }

        .custom-wc-tabs ul.tabs li.active {
            color: #0056b3;
            font-weight: 700;
        }

        .custom-wc-tabs ul.tabs li.active::after {
            content: '';
            position: absolute;
            left: 20px;
            right: 20px;
            bottom: 0;
            height: 3px;
            border-radius: 3px 3px 0 0;
            background: linear-gradient(90deg, #007bff, #00c6ff);
            animation: slideIn 0.4s forwards;
        }

        @keyframes slideIn {
            from {
                width: 0;
                left: 50%;
                right: 50%;
                opacity: 0;
            }

            to {
                width: calc(100% - 40px);
                left: 20px;
                right: 20px;
                opacity: 1;
            }
        }


        .custom-wc-tabs .product-list {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(240px, 1fr));
            gap: 20px;
        }

        .custom-wc-tabs .product-card {
            background: #fff;
            border: 1px solid #eee;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.05);
            padding: 12px;
            display: flex;
            flex-direction: column;
            transition: transform 0.2s ease;
        }

        .custom-wc-tabs .product-card:hover {
            transform: translateY(-4px);
        }

        .custom-wc-tabs .product-card .image-wrapper {
            position: relative;
            border-radius: 8px;
            overflow: hidden;
            margin-bottom: 10px;
        }

        .custom-wc-tabs .product-card img {
            width: 100%;
            height: 200px;
            object-fit: cover;
            transition: transform 0.3s ease;
            display: block;
        }

        .custom-wc-tabs .product-card img:hover {
            transform: scale(1.05);
        }

        .custom-wc-tabs .category-badge {
            position: absolute;
            top: 8px;
            left: 8px;
            background-color: rgba(0, 123, 255, 0.85);
            color: #fff;
            padding: 4px 10px;
            font-size: 0.75rem;
            font-weight: 600;
            border-radius: 12px;
            text-transform: uppercase;
            pointer-events: none;
            user-select: none;
            z-index: 10;
            white-space: nowrap;
        }

        .custom-wc-tabs .product-card h3 {
            font-size: 1rem;
            margin: 0 0 8px;
        }

        .custom-wc-tabs .product-card .price {
            font-weight: bold;
            color: #007bff;
            margin-bottom: 10px;
        }

        .custom-wc-tabs .product-card .button {
            background-color: #007bff;
            color: white;
            padding: 8px 12px;
            text-align: center;
            border-radius: 6px;
            text-decoration: none;
        }

        .product-loader {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(255, 255, 255, 0.7);
            display: none;
            align-items: center;
            justify-content: center;
            z-index: 100;
            border-radius: 12px;
        }

        .spinner {
            border: 4px solid #eee;
            border-top: 4px solid #007bff;
            border-radius: 50%;
            width: 32px;
            height: 32px;
            animation: spin 0.8s linear infinite;
        }

        @keyframes spin {
            0% {
                transform: rotate(0deg);
            }

            100% {
                transform: rotate(360deg);
            }
        }
    </style>

    <div class="custom-wc-tabs">
        <ul class="tabs">
            <li class="tab-button active" data-location="all">All</li>
            <?php
            // Ambil term "Lokasi" sebagai parent
            $lokasi_parent = get_term_by('name', 'Lokasi', 'product_cat');

            if ($lokasi_parent && !is_wp_error($lokasi_parent)) {
                $terms = get_terms(array(
                    'taxonomy' => 'product_cat',
                    'hide_empty' => true,
                    'parent' => $lokasi_parent->term_id // ambil anak dari "Lokasi"
                ));

                foreach ($terms as $term) {
                    echo '<li class="tab-button" data-location="' . esc_attr($term->slug) . '">' . esc_html($term->name) . '</li>';
                }
            }
            ?>
        </ul>

        <div class="product-loader">
            <div class="spinner"></div>
        </div>

        <div class="product-list">
            <?php
            $args = array('post_type' => 'product', 'posts_per_page' => -1, 'post_status' => 'publish');
            $loop = new WP_Query($args);
            while ($loop->have_posts()) : $loop->the_post();
                global $product;
                $terms = get_the_terms(get_the_ID(), 'product_cat');
                $location_slug = '';
                if ($terms && !is_wp_error($terms)) {
                    $location_slug = $terms[0]->slug;
                }
            ?>
                <div class="product-card" data-location="<?php echo esc_attr($location_slug); ?>">
                    <a href="<?php the_permalink(); ?>">
                        <div class="image-wrapper">
                            <?php if (has_post_thumbnail()) {
                                the_post_thumbnail('medium');
                            } ?>
                            <?php if ($terms && !is_wp_error($terms)) : ?>
                                <div class="category-badge"><?php echo esc_html($terms[0]->name); ?></div>
                            <?php endif; ?>
                        </div>
                        <h3><?php the_title(); ?></h3>
                    </a>
                    <div class="price"><?php echo $product->get_price_html(); ?></div>
                </div>
            <?php endwhile;
            wp_reset_postdata(); ?>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const tabs = document.querySelectorAll('.custom-wc-tabs .tab-button');
            const cards = document.querySelectorAll('.custom-wc-tabs .product-card');
            const loader = document.querySelector('.product-loader');

            tabs.forEach(tab => {
                tab.addEventListener('click', function() {
                    tabs.forEach(t => t.classList.remove('active'));
                    this.classList.add('active');

                    const location = this.getAttribute('data-location');

                    loader.style.display = 'flex';

                    setTimeout(() => {
                        cards.forEach(card => {
                            const cardLocation = card.getAttribute('data-location');
                            if (location === 'all' || cardLocation === location) {
                                card.style.display = 'flex';
                            } else {
                                card.style.display = 'none';
                            }
                        });

                        loader.style.display = 'none';
                    }, 300);
                });
            });
        });
    </script>
<?php
    return ob_get_clean();
}
add_shortcode('custom_wc_tabs', 'custom_wc_tabs_shortcode');
