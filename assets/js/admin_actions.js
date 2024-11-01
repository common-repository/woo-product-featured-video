jQuery(document).ready(function($){
    var add_slide_frame,
        change_slide_frame;

    jQuery('.select_featured_video').on('click',function(e){
      e.preventDefault();

      if(add_slide_frame){
        add_slide_frame.open();
        return;
      }

      add_slide_frame= wp.media({
        frame: 'video',
        state: 'video-details',
        multiple: false,
      });

      add_slide_frame.on('update',function(){
        var extension  = add_slide_frame.state().media.extension;
        var video_url  = add_slide_frame.state().media.attachment.changed.url;
        var video_icon = add_slide_frame.state().media.attachment.changed.icon;
        var video_title= add_slide_frame.state().media.attachment.changed.title;
        var video_desc = add_slide_frame.state().media.attachment.changed.description;


          if($('.featured-product-video-container video').length == 0){
              $('.featured-product-video-container .featured-product-video-enabled').after('<video controls width="100%" ><source src="" ></video>');
          }

        $('.featured-product-video-container video source').attr({'src':video_url});
        $('input[name="woo_product_featured_video_url"]').val(video_url);

      });

    add_slide_frame.open();

  });

})