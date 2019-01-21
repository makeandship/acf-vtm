(function($) {
  function initialize_field($el) {
    //$el.doStuff();
  }

  if (acf) {
    var VTM = acf.Field.extend({
      type: "vtm",
      select2: false,

      wait: "load",

      events: {
        removeField: "onRemove"
      },

      $input: function() {
        return this.$("select");
      },

      initialize: function() {
        // vars
        var $select = this.$input();

        // inherit data
        this.inherit($select);

        // select2
        if (this.get("ui")) {
          // populate ajax_data (allowing custom attribute to already exist)
          var ajaxAction = this.get("ajax_action");
          if (!ajaxAction) {
            ajaxAction = "acf/fields/" + this.get("type") + "/query";
          }

          // select2
          this.select2 = acf.newSelect2($select, {
            field: this,
            ajax: this.get("ajax"),
            multiple: this.get("multiple"),
            placeholder: this.get("placeholder"),
            allowNull: this.get("allow_null"),
            ajaxAction: ajaxAction
          });
        }
      },

      onRemove: function() {
        if (this.select2) {
          this.select2.destroy();
        }
      }
    });

    acf.registerFieldType(VTM);
  }

  if (typeof acf.add_action !== "undefined" && true === false) {
    // acf.fields.vtm = acf.fields.select.extend({
    //   type: "vtm",
    //   minimumInputLength: 1,
    //   quietMillis: 100 //or 100 or 10
    // });

    /*
		*  ready append (ACF5)
		*
		*  These are 2 events which are fired during the page load
		*  ready = on page load similar to $(document).ready()
		*  append = on new DOM elements appended via repeater field
		*
		*  @type	event
		*  @date	20/07/13
		*
		*  @param	$el (jQuery selection) the jQuery element which contains the ACF fields
		*  @return	n/a
		*/

    acf.add_action("ready append", function($el) {
      // search $el for fields of type 'FIELD_NAME'
      acf.get_fields({ type: "vtm" }, $el).each(function() {
        initialize_field($(this));
      });
    });

    acf.add_filter("select2_args", function(args, $select, settings) {
      // do something to args
      var s = "ss";

      // return
      return args;
    });
  } else {
    /*
		*  acf/setup_fields (ACF4)
		*
		*  This event is triggered when ACF adds any new elements to the DOM.
		*
		*  @type	function
		*  @since	1.0.0
		*  @date	01/01/12
		*
		*  @param	event		e: an event object. This can be ignored
		*  @param	Element		postbox: An element which contains the new HTML
		*
		*  @return	n/a
		*/

    $(document).on("acf/setup_fields", function(e, postbox) {
      $(postbox)
        .find('.field[data-field_type="vtm"]')
        .each(function() {
          initialize_field($(this));
        });
    });
  }
})(jQuery);
