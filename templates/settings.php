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

    </div>
</div>