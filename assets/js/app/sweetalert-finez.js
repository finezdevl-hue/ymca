// ============================================================
// Early Page Loader Orchestrator — White Background Spinner
// Injects #page-loader div immediately (in <head>)
// Tracks ALL XHR + fetch — hides loader when everything is done
// ============================================================
(function() {
    var done = false;

    // Inject the loader div into <body> as soon as DOM is available
    function injectLoader() {
        if (document.getElementById('page-loader')) return;
        var loader = document.createElement('div');
        loader.id = 'page-loader';
        loader.innerHTML =
            '<div class="loader-rings">' +
                '<div class="loader-dot"></div>' +
            '</div>' +
            '<div class="loader-text">Loading&hellip;</div>';
        // Insert at beginning of body if ready, otherwise queue it
        if (document.body) {
            document.body.insertBefore(loader, document.body.firstChild);
        } else {
            document.addEventListener('DOMContentLoaded', function() {
                if (!document.getElementById('page-loader')) {
                    document.body.insertBefore(loader, document.body.firstChild);
                }
            });
        }
    }
    injectLoader();
    document.addEventListener('DOMContentLoaded', injectLoader);

    function hideLoader() {
        var loader = document.getElementById('page-loader');
        if (loader) {
            loader.classList.add('hide');
            setTimeout(function() {
                if (loader.parentNode) loader.parentNode.removeChild(loader);
            }, 450);
        }
        document.body.classList.add('loaded');
    }

    window.__tryMarkPageLoaded = function() {
        if (done) return;
        done = true;
        hideLoader();
    };

    // Safety fallback: max 1.5s wait
    setTimeout(function() {
        if (!done) { done = true; hideLoader(); }
    }, 1500);

    window.addEventListener('load', function() {
        setTimeout(window.__tryMarkPageLoaded, 100);
    });
})();

function alertsuccess(text) {

    
    swal({

        title: "Success!",
        text: text,
        type: "success"
    });
}

function alerterror(obj, xhr) {
    var text = '';
    if (obj && obj.Message) {
        text = obj.Message;
    } else if (obj && obj.errmsg) {
        text = obj.errmsg;
    } else if (typeof obj === 'string') {
        text = obj;
    } else {
        text = "An unexpected error occurred.";
    }
    swal({
        title: "Error!",
        text: text,
        type: "warning"
    });
}
    function alertinfo(text) {
        swal({
            title: "Info!",
            text:text,
            type: "info"
        });
        }
        
   function alertwarning(text) {
            swal({
                title: "Warning!",
                text:text,
                type: "warning"
            });
            }


   //function getConfirmation() {
       
   //    swal({
   //        title: "Are you sure?",
   //        text: "Do you want save this data!",
   //        type: "warning",
   //        showCancelButton: true,
   //        confirmButtonColor: "#DD6B55",
   //        confirmButtonText: "Yes,Save!",
   //        closeOnConfirm: true
   //    }.then(function (isConfirm) {
   //        if (isConfirm) {
   //            return true;
   //        } else {
   //            return false;
   //        }
   //    })
   //    );
   //}


   function getSaveConfirmation() {
      alert("test1");
       swal({
           title: "Are you sure?",
           text: "Do you want to save this data!",
           type: "warning",
           showCancelButton: true,
           confirmButtonColor: "#DD6B55",
           confirmButtonText: "Yes,Save!",
           cancelButtonText: "Cancel",
           closeOnConfirm: true,
           closeOnCancel: true
       },
    function (isConfirm) {
        if (isConfirm) {
            return 1;
        } else {
            return 0;
        }
    }
);
}
   


