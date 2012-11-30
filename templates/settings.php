<?php if( $flash ) { ?>
    <div id="message" class="updated fade">
        <p><strong><?php echo $flash; ?></strong></p>
    </div>
<?php } ?>
<div id="icon-tools" class="icon32"><br /></div>
<div class="wrap">
    <h2><?php _e( 'Localize WordPress','localize' ); ?></h2>
    <div id="poststuff" class="metabox-holder">

        <form id="plugin-filter" method="post">
            <?php
                echo '<input type="hidden" name="page" value="'. $_REQUEST[ 'page' ] .'" />';
                $list_table->prepare_items();
                $list_table->display();
            ?>
        </form>
        
        <div class="postbox">
            <h3 class="hndle" ><?php _e( 'Options','localize' )?></h3>
            <div class="inside">
                <p><?php _e( "Please select your language and it's version:",'localize' ); ?></p>
                <form action="" method="post">
                    <?php wp_nonce_field( 'localize', 'localize_nonce' ); ?>
                    <p class="form-field">
                        <label for="lang"><?php _e( 'Localization Code','localize' )?></label>
                        <input id="lang" name="lang" type="text" style="width: 100px;" value="<?php echo $lang ?>"/>
                        <?php _e( 'The localization code is composed of two letters language code, an underscore and two letters country code.','localize' ); ?>
                    </p>
                    <p>
                        <?php _e( 'Follow these links to find out your localization code: ','localize' ); ?>
                        <a href="http://www.gnu.org/software/hello/manual/gettext/Usual-Language-Codes.html">
                            <?php _e( 'Language Codes List','localize' ); ?>
                        </a>,
                        <a href="http://www.gnu.org/software/hello/manual/gettext/Country-Codes.html">
                            <?php _e( 'Country Codes List','localize' ); ?>
                        </a>.
                    </p>
                    <p class="form-field">
                        <label for="lang_version"><?php _e( 'Localization Version','localize' ); ?></label>
                        <select id="lang_version" name="lang_version">
                            <?php if ( empty( $versions ) ) : ?>
                                <option value=""><?php _e( 'None available','localize' ); ?></option>
                            <?php else: ?>
                                <?php foreach ( $versions as $name => $slug ): ?>
                                    <option value="<?php echo esc_attr( $slug ); ?>" <?php selected( esc_attr( $slug ), $lang_version ); ?> ><?php
                                        echo esc_html( $name );
                                    ?></option>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </select>
                    </p>
                    <p>
                        <input type="submit" class="button-primary" value="<?php _e( 'Save Changes' )?>"/>
                    </p>
                </form>
            </div>
        </div>
    </div>
</div>