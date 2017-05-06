$.each({
	successMessage: function(msg){
		$.univ().notify('Success',msg,'success',null,null,null);
	},
	errorMessage: function(msg){
		$.univ().notify('Error',msg,'error',null,null,null);
	}
},$.univ._import);

(function ($) {
      $.each(['show', 'hide'], function (i, ev) {
        var el = $.fn[ev];
        $.fn[ev] = function () {
          this.trigger(ev);
          return el.apply(this, arguments);
        };
      });
    })(jQuery);

$.ui.dialog.prototype._allowInteraction = function(e) {
    return !!$(e.target).closest('.ui-dialog, .ui-datepicker, .select2-dropdown, .mce-window').length;
};