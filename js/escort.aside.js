!function(e,t,i){"use strict";function s(i){this.$wrapper=e(i),this.$trigger=this.$wrapper.find(".escort-aside-trigger"),this.id=this.$trigger.data("escort-aside"),this.dialogType=this.$trigger.data("dialog-type"),this.display=this.$trigger.data("escort-aside-display"),this.$content=e("#escort-ajax-"+this.id),this.usesAjax="undefined"!=typeof this.$trigger.data("escort-ajax"),this.$document=e(t),this.$body=e("body"),this.setup()}e.extend(s,{instances:[]}),e.extend(s.prototype,{active:!1,usedAjax:!1,setup:function(){var e=this;e.ajax(),e.$trigger.on("click.aside",function(t){t.preventDefault(),e.active?e.hide():(e.dialogType?i.Escort.hideFull():e.$trigger.off("click.aside.ajax"),e.show())})},ajax:function(){var t=this;t.usesAjax&&"undefined"!=typeof i.ajax&&t.$trigger.once("aside-ajax").each(function(){var s={};s.progress={type:"fullscreen"},s.url=e(this).attr("href"),s.event="click.aside.ajax",s.progress={type:"fullscreen"},t.dialogType&&(s.dialogType=t.dialogType,s.dialog=e(this).data("dialog-options")),s.base=e(this).attr("id"),s.element=this,i.ajax(s)})},show:function(t){var i=this;i.active||(i.active=!0,i.usesAjax&&!i.usedAjax&&(i.$document.trigger("click.escort-aside"),i.usedAjax=!0),i.$wrapper.addClass("escort-active"),i.$content.addClass("escort-active"),setTimeout(function(){i.$document.on("click.escort-aside."+i.id,function(t){e(t.target).closest(".escort-aside-content").length||i.hide()}),i.$document.on("escort-region:show."+i.id+" escort-region:hide."+i.id,function(e,t){i.hide()})},10))},hide:function(e){var t=this;t.active&&(t.active=!1,t.$wrapper.removeClass("escort-active"),t.$content.removeClass("escort-active"),t.$document.off("click.escort-aside."+t.id),t.$document.off("escort-region:show."+t.id+" escort-region:hide."+t.id))}}),i.behaviors.escortAside={attach:function(t){var i=e(t).find(".escort-aside").once("escort-aside");if(i.length)for(var a=0;a<i.length;a++)s.instances.push(new s(i[a]));this.updateDestinations(t)},updateDestinations:function(t){function i(e,t,i){var s=new RegExp("([?&])"+t+"=.*?(&|$)","i");return e.match(s)?e.replace(s,"$1"+t+"="+i+"$2"):e}var s,a=drupalSettings.path.baseUrl+drupalSettings.path.currentPath;e(".escort-aside-content a").once("escort-aside-content").each(function(){s=e(this).attr("href"),s=i(s,"destination",a),e(this).attr("href",s)})}},i.EscortAsides=s}(jQuery,document,Drupal);