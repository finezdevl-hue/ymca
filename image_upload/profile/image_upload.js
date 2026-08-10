const cropModal = document.getElementById('cropModal');
const modalPreview = document.getElementById('modalPreview');
let cropper;

// Show the modal and initialize the cropper starts
function photoInputChange(event){
    const file = event.target.files[0];
    if (file) {
        const reader = new FileReader();
        reader.onload = () => {
            modalPreview.src = reader.result;
            cropModal.style.display = 'flex';
    
            // Wait for the image to load before initializing the cropper
            modalPreview.onload = () => {
                if (cropper) cropper.destroy();
                cropper = new Cropper(modalPreview, {
                    aspectRatio: 2/2,  // Fixed aspect ratio (4:3)
                    cropBoxResizable: false,  // Disable resizing of the crop box
                    viewMode: 2,  // Restrict the crop box within the image boundary
                    dragMode: 'move',  // Allow moving the crop box
                    movable: false, //disable image movement
                });
            };
        };
        reader.readAsDataURL(file);
    }
}
// Show the modal and initialize the cropper ends
       
// function to close the cropper modal starts
function closeModalClicked(){
    closeModel();          
}
// function to close the cropper modal ends
          
// function to crop the selected image starts
function cropButtonClicked(){
    if (cropper) {
        cropper.getCroppedCanvas().toBlob((blob) => {
            const formData = new FormData();
            formData.append('croppedImage', blob, 'cropped.jpg');
                   
            fetch('../image_upload/profile/upload.php', {
                method: 'POST',
                body: formData,
            })
            .then(response => response.json())
            .then(data => {
                // alert(data.filename);
                closeModel();
                $("#hdn_file_upload").val(data.filename);
            })
            .catch(error => {
                console.error('Error:', error);
            });
        });
    }
}
// function to crop the selected image ends

// function to distroy cropper and close croper modal starts
function closeModel(){
    cropModal.style.display = 'none';
    if (cropper) cropper.destroy();
}
// function to distroy cropper and close cropper modal ends
        
    
    
    






