!function(t,r){"use strict";function o(r){this.$wrapper=t(r),this.$trigger=this.$wrapper.find(".escort-dropdown-trigger"),this.setup()}t.extend(o,{instances:[]}),t.extend(o.prototype,{setup:function(){var t=this;console.log(t.$trigger)}}),Drupal.behaviors.escortDropdown={attach:function(r){var e=t(r).find(".escort-dropdown").once("escort-dropdown");if(e.length)for(var n=0;n<e.length;n++)o.instances.push(new o(e[n]))}},Drupal.EscortDropdowns=o}(jQuery,document);