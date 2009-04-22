/**
 * Handle: wpCPStoreAdmin
 * Version: 0.0.1
 * Deps: jquery
 * Enqueue: true
 */

var wpCPStoreAdmin = function () {}

wpCPStoreAdmin.prototype = {
    options           : {},
    generateShortCode : function() {
        var content = this['options']['content'];
        delete this['options']['content'];

        var attrs = '';
		var content = document.getElementById('wpCPStore_url').value;
		var returnnum = document.getElementById('wpCPStore_return').value;
			if (returnnum != '') {
                attrs += ' return="' + returnnum + '"';
            }
		var previewnum = document.getElementById('wpCPStore_preview').value;
			if (returnnum != '') {
                attrs += ' preview="' + previewnum + '"';
            }
		return '[cpstore' + attrs + ']' + content + '[/cpstore]'
    },
    sendToEditor      : function(f) {
        var collection = jQuery(f).find("input[id^=wpCPStoreName]:not(input:checkbox),input[id^=wpCPStoreName]:checkbox:checked");
        var $this = this;
        collection.each(function () {
            var name = this.name.substring(13, this.name.length-1);
            $this['options'][name] = this.value;
        });
        send_to_editor(this.generateShortCode());
        return false;
    }
}

var this_wpCPStoreAdmin = new wpCPStoreAdmin();