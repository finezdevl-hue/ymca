const cropModal = document.getElementById('cropModal');
const modalPreview = document.getElementById('modalPreview');
let cropper;
    
// Show the modal and initialize the cropper starts
function photoInputChange(){
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
                    aspectRatio: 3/4,  // Fixed aspect ratio (4:3)
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
// show the modal adn initialze the cropper ends
        
// Close the modal starts
function closeModalClicked(){
    closeModel();          
}
// Close the modal  ends

// Crop the image and upload starts
function cropButtonClicked(){
    if (cropper) {
        cropper.getCroppedCanvas().toBlob((blob) => {
            const formData = new FormData();
            formData.append('croppedImage', blob, 'cropped.jpg');
            fetch('../image_upload/family/upload.php', {
                method: 'POST',
                body: formData,
            })
            .then(response => response.json())
            .then(data => {
                closeModel();
                $("#hdn_file_upload").val(data.filename);
            })
            .catch(error => {
                console.error('Error:', error);
            });
        });
    }
}
// Crop the image and upload ends

// function to close the modal starts
function closeModel(){
    cropModal.style.display = 'none';
    if (cropper) cropper.destroy();
    $("#clientModal").modal("show");
}
// function to close the modal ends
    
    
    






