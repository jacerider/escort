!function(t,e,o){"use strict";function i(o){this.$wrapper=t(o),this.$trigger=this.$wrapper.find(".escort-dropdown-trigger"),this.usesAjax="undefined"!=typeof this.$trigger.data("escort-ajax"),this.$document=t(e),this.$body=t("body"),this.setup()}t.extend(i,{instances:[]}),t.extend(i.prototype,{active:!1,setup:function(){var t=this;t.ajax(),t.$trigger.on("click.dropdown",function(e){e.preventDefault(),t.$trigger.off("click.dropdown.ajax"),t.active?t.hide():t.show()})},ajax:function(){var e=this;e.usesAjax&&"undefined"!=typeof o.ajax&&e.$trigger.once("dropdown-ajax").each(function(){var e={};e.progress={type:"fullscreen"},e.url=t(this).attr("href"),e.event="click.dropdown.ajax",e.dialogType=t(this).data("dialog-type"),e.dialog=t(this).data("dialog-options"),e.base=t(this).attr("id"),e.element=this,o.ajax(e)})},show:function(e){var o=this;o.active||(o.active=!0,o.$wrapper.addClass("escort-active"),setTimeout(function(){o.$document.on("click.escort-dropdown",function(e){t(e.target).closest(".escort-dropdown-content").length||o.hide()}),o.$document.on("escort-region:show escort-region:hide",function(t,e){o.hide()})},10))},hide:function(t){var e=this;e.active&&(e.active=!1,e.$wrapper.removeClass("escort-active"),e.$document.off("click.escort-dropdown"),e.$document.off("escort-region:show escort-region:hide"))}}),o.behaviors.escortDropdown={attach:function(e){var o=t(e).find(".escort-dropdown").once("escort-dropdown");if(o.length)for(var n=0;n<o.length;n++)i.instances.push(new i(o[n]))}},o.EscortDropdowns=i}(jQuery,document,Drupal);