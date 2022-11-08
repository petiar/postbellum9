jQuery(function(){
  jQuery('input[type="radio"]').click(function(){
    if (jQuery(this).is(':checked'))
    {
      console.log(jQuery(this).val());
    }
  });
});
