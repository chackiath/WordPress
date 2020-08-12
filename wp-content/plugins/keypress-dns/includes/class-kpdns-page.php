<?php

/**
 * Class KPDNS_Utils.
 *
 * @since 1.2.0
 */
abstract class KPDNS_Page {

    const ITEMS_PER_PAGE = 20;

	protected $tabs;

	public function render() {
	    ?>
        <div class="wrap <?php $_GET['page']; ?>" id="kpdns-wrap">
            <h1><?php get_admin_page_title(); ?></h1>
            <?php

                $this->maybe_render_admin_notice();

                $this->maybe_render_tabs();

                ?>
                <div id="kpdns-main">
                    <?php $this->render_main_content(); ?>
                </div>
                <?php
            ?>
        </div><!--end wrap-->
        <?php
    }

    abstract protected function render_main_content();

	public static function render_view( $view, $args = array() ) {
		$view_path = KPDNS_PLUGIN_DIR . '/views/' . $view . '.php';

		if ( ! is_readable( $view_path ) ) {
			return;
		}

		if ( ! empty( $args ) ) {
			extract( $args, EXTR_OVERWRITE ); // phpcs:ignorei
        }

		include $view_path;
    }

	public static function render_error( $error ) {
		if ( is_wp_error( $error ) && $error->has_errors() ) {
			?>
            <ul class="kpdns-errors">
				<?php
				foreach( $error->get_error_messages() as $message ) {
					?>
                    <li><?php echo $message ?></li>
					<?php
				}
				?>
            </ul>
			<?php
		}
	}

	public static function render_search_box( $action, $label, $button_text ) {
        ?>
            <form method="post" action="<?php esc_attr_e( $action ); ?>">
                <p class="search-box">
                    <label class="screen-reader-text" for="kpdns-search-input"><?php esc_html_e( $label );?></label>
                    <input type="search" id="kpdns-search-input" name="search" value="<?php echo isset( $_POST['search'] ) ? esc_attr( $_POST['search'] ) : '' ?>">
                    <input type="submit" id="kpdns-search-submit" class="button" value="<?php esc_attr_e( $button_text );?>">
                </p>
            </form>
        <?php
    }

	public static function build_query_args( $page, $updated, $messages, $args = array() ) {
		$query_arg = array(
			'page'     => urlencode( $page ),
			'updated'  => urlencode( $updated ? 'true' : 'false' ),
			'messages' => urlencode_deep( $messages ),
		);

		if ( ! empty( $args ) ) {
			foreach ( $args as $key => $value ) {
				$query_arg[ $key ] = is_array( $value ) ? urlencode_deep( $value ) : urlencode( $value );
			}
		}
		return $query_arg;
	}

	static function check_errors( $object, $args ) {
		if ( is_wp_error( $object ) ) {
			$query_arg = array(
				'updated'  => 'false',
				'messages' => urlencode_deep( $object->get_error_messages() ),
			);

			if ( isset( $args ) && ! empty( $args ) ) {
				foreach ( $args as $key => $value ) {
                   if ( is_array( $value ) ) {
						$query_arg[ $key ] = urlencode_deep( $value );
					} else {
						$query_arg[ $key ] = urlencode( $value );
					}
				}
			}

			wp_redirect( add_query_arg( $query_arg, self::get_admin_url() ) );
			exit();
		}
	}

	static function get_admin_url() {
	    if ( is_multisite() ) {
	        return network_admin_url( 'admin.php' );
        } else {
	        return admin_url( 'admin.php' );
        }
    }

	/**
	 * Returns the admin url that handles actions.
	 *
	 * @since 0.1.0
	 * @return string Admin URL link.
	 */
	static function get_admin_action_url() {
		if ( is_multisite() ) {
			return network_admin_url( 'edit.php' );
		} else {
			return admin_url( 'admin-post.php' );
		}
	}

	static function get_form_action_url( $action ) {
		$action_url = self::get_admin_action_url();
		$action_url .= '?action=' . $action;
		$action_url .= '&kpdns-page=' . $_REQUEST['page'];
		return $action_url;
	}

	static function get_link_action_url( $action, $nonce, $args = false ) {

		$query_args = array(
            'action'     => $action,
        );

		if ( isset( $_REQUEST['page'] ) ) {
			$query_args['kpdns-page'] = $_REQUEST['page'];
		}

		if ( isset( $_REQUEST['tab'] ) ) {
			$query_args['tab'] = $_REQUEST['tab'];
		}

		if ( isset( $_REQUEST['view'] ) ) {
			$query_args['view'] = $_REQUEST['view'];
		}

		if ( $args ) {
		    foreach ( $args as $key => $value ) {
			    $query_args[ $key ] = $value;
            }
        }

		return wp_nonce_url( add_query_arg( $query_args, self::get_admin_action_url() ), $action, $nonce );
    }

    private function maybe_render_tabs() {
	    if ( ! isset( $this->tabs ) || ! is_array( $this->tabs ) || empty( $this->tabs ) ) {
	        return;
        }
        ?>
        <h2 class="nav-tab-wrapper">
            <?php
            foreach ( $this->tabs as $tab ) {
                $url   = self::get_admin_url() . "?page={$_REQUEST['page']}&tab={$tab['id']}";
                $class = 'nav-tab';

                if ( $this->get_current_tab_id() === $tab['id'] ) {
                    $class .= ' nav-tab-active';
                }

                ?>
                <a href="<?php esc_attr_e( esc_url( $url ) ); ?>" class="<?php esc_attr_e( $class ); ?>"
                   id="<?php esc_attr_e( 'kpui-tab-' . $tab['id'] ) ?>"><?php esc_html_e( $tab['name'] ) ?></a>
                <?php
            }
            ?>
        </h2>
        <?php
    }

    protected function get_current_tab_id() {

	    if ( ! isset( $this->tabs ) ) {
	        return null;
        }

	    if ( isset( $_REQUEST['tab'] ) ) {
		    $current_tab_id = $_REQUEST['tab'];

		    if ( isset( $current_tab_id ) && isset( $this->tabs[ $current_tab_id ] ) ) {
			    return $current_tab_id;
		    }
        }

	    return reset($this->tabs )['id'];
    }

	protected function get_current_view_id() {

		if ( ! isset( $this->views ) ) {
			return null;
		}

		if ( isset( $_REQUEST['view'] ) ) {
			$current_view_id = $_REQUEST['view'];

			if ( isset( $current_view_id ) && isset( $this->views[ $current_view_id ] ) ) {
				return $current_view_id;
			}
		}

		return reset($this->views )['id'];
	}

    private function maybe_render_admin_notice() {
	    if ( ! isset( $_GET['messages'] ) ) {
	        return;
	    }

	    $class = 'notice-error';

        if ( isset( $_GET['updated'] ) ) {
            if ( 'true' === $_GET['updated']  ) {
                $class = 'notice-success';
            }
        } elseif ( isset( $_GET['warning'] ) ) {
            $class = 'notice-warning';
        }

        ?>
        <div id="notice"
             class="<?php esc_attr_e( $class ); ?> notice is-dismissible">
            <ul>
                <?php
                foreach ( $_GET['messages'] as $message ) {
                    ?>
                    <li><?php echo wp_unslash( wp_specialchars_decode( $message ) ) ?></li>
                    <?php
                }
                ?>
            </ul>
        </div>
        <?php
    }


	public static function render_tooltip( $text ) {
		echo self::get_tooltip_hml( $text );
	}

	public static function get_tooltip_hml( $text ) {
	    return sprintf(
	            '<span alt="f223" class="kpdns-tooltip kpdns-tooltip-icon" title="%s"></span>',
                $text
        );
    }

    /**
     * @param $args array
     */
    public static function render_pagination( $total_items, $total_pages, $current, $which = 'top' ) {
        if ( 'top' === $which && $total_pages > 1 ) {
            //$this->screen->render_screen_reader_content( 'heading_pagination' );
        }

        $output = '<span class="displaying-num">' . sprintf(
            /* translators: %s: Number of items. */
                _n( '%s item', '%s items', $total_items ),
                number_format_i18n( $total_items )
            ) . '</span>';

        $removable_query_args = wp_removable_query_args();
        $removable_query_args[] = 'messages';

        $current_url = set_url_scheme( 'http://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'] );

        $current_url = remove_query_arg( $removable_query_args, $current_url );

        $page_links = array();

        $total_pages_before = '<span class="paging-input">';
        $total_pages_after  = '</span></span>';

        $disable_first = false;
        $disable_last  = false;
        $disable_prev  = false;
        $disable_next  = false;

        if ( $current == 1 ) {
            $disable_first = true;
            $disable_prev  = true;
        }
        if ( $current == 2 ) {
            $disable_first = true;
        }
        if ( $current == $total_pages ) {
            $disable_last = true;
            $disable_next = true;
        }
        if ( $current == $total_pages - 1 ) {
            $disable_last = true;
        }

        if ( $disable_first ) {
            $page_links[] = '<span class="tablenav-pages-navspan button disabled" aria-hidden="true">&laquo;</span>';
        } else {
            $page_links[] = sprintf(
                "<a class='first-page button' href='%s'><span class='screen-reader-text'>%s</span><span aria-hidden='true'>%s</span></a>",
                esc_url( remove_query_arg( 'paged', $current_url ) ),
                __( 'First page' ),
                '&laquo;'
            );
        }

        if ( $disable_prev ) {
            $page_links[] = '<span class="tablenav-pages-navspan button disabled" aria-hidden="true">&lsaquo;</span>';
        } else {
            $page_links[] = sprintf(
                "<a class='prev-page button' href='%s'><span class='screen-reader-text'>%s</span><span aria-hidden='true'>%s</span></a>",
                esc_url( add_query_arg( 'paged', max( 1, $current - 1 ), $current_url ) ),
                __( 'Previous page' ),
                '&lsaquo;'
            );
        }

        if ( 'bottom' === $which ) {
            $html_current_page  = $current;
            $total_pages_before = '<span class="screen-reader-text">' . __( 'Current Page' ) . '</span><span id="table-paging" class="paging-input"><span class="tablenav-paging-text">';
        } else {
            $html_current_page = sprintf(
                "%s<input class='current-page' id='current-page-selector' type='text' name='paged' value='%s' size='%d' aria-describedby='table-paging' /><span class='tablenav-paging-text'>",
                '<label for="current-page-selector" class="screen-reader-text">' . __( 'Current Page' ) . '</label>',
                $current,
                strlen( $total_pages )
            );
        }
        $html_total_pages = sprintf( "<span class='total-pages'>%s</span>", number_format_i18n( $total_pages ) );
        $page_links[]     = $total_pages_before . sprintf(
            /* translators: 1: Current page, 2: Total pages. */
                _x( '%1$s of %2$s', 'paging' ),
                $html_current_page,
                $html_total_pages
            ) . $total_pages_after;

        if ( $disable_next ) {
            $page_links[] = '<span class="tablenav-pages-navspan button disabled" aria-hidden="true">&rsaquo;</span>';
        } else {
            $page_links[] = sprintf(
                "<a class='next-page button' href='%s'><span class='screen-reader-text'>%s</span><span aria-hidden='true'>%s</span></a>",
                esc_url( add_query_arg( 'paged', min( $total_pages, $current + 1 ), $current_url ) ),
                __( 'Next page' ),
                '&rsaquo;'
            );
        }

        if ( $disable_last ) {
            $page_links[] = '<span class="tablenav-pages-navspan button disabled" aria-hidden="true">&raquo;</span>';
        } else {
            $page_links[] = sprintf(
                "<a class='last-page button' href='%s'><span class='screen-reader-text'>%s</span><span aria-hidden='true'>%s</span></a>",
                esc_url( add_query_arg( 'paged', $total_pages, $current_url ) ),
                __( 'Last page' ),
                '&raquo;'
            );
        }

        $pagination_links_class = 'pagination-links';

        $output .= "\n<span class='$pagination_links_class'>" . join( "\n", $page_links ) . '</span>';

        if ( $total_pages ) {
            $page_class = $total_pages < 2 ? ' one-page' : '';
        } else {
            $page_class = ' no-pages';
        }
        $output = "<div class='tablenav-pages{$page_class}'>$output</div>";

        echo $output;
    }

    public static function render_form_opening( $attrs = array() ) {
        $output = '<form';

        if ( ! isset( $attrs['method'] ) ) {
            $output .= ' method="post"';
        }

        foreach( $attrs as $key => $value ) {
            $output .= sprintf( ' %1$s="%2$s"', $key, esc_attr( $value ) );
        }
        $output .= '>';
        echo $output;
    }

    public static function render_form_closing() {
        echo '</form>';
    }

    public static function render_bulk_actions( $actions, $location = 'top', $action_name='action' ) {
        ?>
            <div class="alignleft actions bulkactions" id="bulkactions">
                <div class="bulkactions-fields" style="display: inline-block;">
                    <label for="bulk-action-selector-<?php echo $location; ?>" class="screen-reader-text"><?php _e( 'Select bulk action', 'keypress-dns' ) ?></label>
                    <select name="<?php echo esc_attr( $action_name ) ?>" id="bulk-action-selector-<?php echo $location; ?>" class="bulk-action-selector">
                        <option value="-1"><?php _e( 'Bulk Actions', 'keypress-dns' ) ?></option>
                        <?php
                            foreach( $actions as $action ) {
                                if ( isset( $action['value'] ) && isset( $action['label'] ) ) {
                                    echo sprintf( '<option value="%1$s">%2$s</option>', $action['value'], $action['label'] );
                                }
                            }
                        ?>
                    </select>
                </div>
                <input type="submit" id="doaction" name="submit" class="button action" value="<?php esc_attr_e( 'Apply', 'keypress-dns' ) ?>">
            </div>
        <?php
    }
}
