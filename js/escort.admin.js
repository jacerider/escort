!function(t,e,o){"use strict";o.behaviors.escortAdminSort={attach:function(e,r){function s(e,r){if(!i){i=!0;var s,n={};c.each(function(){s=t(this).data("escort-region"),t(this).find(".escort-sortable").each(function(e){n[t(this).data("escort-id")]={region:s,weight:e}})}),t.ajax({url:o.url("admin/config/user-interface/escort/update"),type:"POST",data:JSON.stringify(n),dataType:"json",success:function(t){}}),setTimeout(function(){i=!1},10)}}var c=t(".escort-sort",e),n=c.once("escort-admin-sort"),i=!1;n.length&&n.sortable({items:".escort-sortable",connectWith:".escort-sort",placeholder:"escort-placeholder",forcePlaceholderSize:!0,tolerance:"pointer",helper:"clone",appendTo:t("body"),handle:"> .escort-item",opacity:.5,scroll:!1,start:function(){t("body").addClass("escort-sorting")},stop:function(){t("body").removeClass("escort-sorting")},update:s}).disableSelection()}}}(jQuery,window,Drupal);