// function to validate add members form starts
function validate_name(event) {
    const first_nameInput = event.target;
    const middle_nameInput = event.target;
    const last_nameInput = event.target;
    const father_nameInput = event.target;
    const mother_nameInput = event.target;

    const first_name = first_nameInput.value;
    const middle_name = middle_nameInput.value;
    const last_name = last_nameInput.value;
    const father_name = father_nameInput.value;
    const mother_name = mother_nameInput.value;
    
    const pattern = /^[a-zA-Z\s]+$/; // Allows only specified characters

    // Check if password contains only allowed characters
    if (!pattern.test(first_name) || !pattern.test(middle_name) || !pattern.test(last_name)) {
        // alertwarning("Invalid charecter found. use letters Avoid other charecters and numbers.");
        // Remove the last character
        first_nameInput.value = first_name.replace(/[^a-zA-Z]/g, "");
        middle_nameInput.value = middle_name.replace(/[^a-zA-Z]/g, "");
        last_nameInput.value = last_name.replace(/[^a-zA-Z]/g, "");
        father_nameInput.value = father_name.replace(/[^a-zA-Z]/g, "");
        mother_nameInput.value = mother_name.replace(/[^a-zA-Z]/g, "");
       
    }
}
// Event listener to validate inputs on each keyup starts
function nameValidation(){
    const first_nameInput = document.getElementById('txt_first_name');
    const middle_nameInput = document.getElementById('txt_middle_name');
    const last_nameInput = document.getElementById('txt_last_name');
    const father_nameInput = document.getElementById('txt_father_name');
    const mother_nameInput = document.getElementById('txt_mother_name');
   
    first_nameInput.addEventListener('keyup', validate_name);
    middle_nameInput.addEventListener('keyup', validate_name);
    last_nameInput.addEventListener('keyup', validate_name);
    father_nameInput.addEventListener('keyup', validate_name);
    mother_nameInput.addEventListener('keyup', validate_name);
};
// function to validate add mebers form ends



// function to validate add group form starts
function validate_group(event) {

    const group_nameInput = event.target;
    const group_name = group_nameInput.value;
    const pattern = /^[a-zA-Z\s]+$/; // Allows only specified characters

    // Check if password contains only allowed characters
    if (!pattern.test(group_name)) {
        // Remove the last character
        group_nameInput.value = group_name.replace(/[^a-zA-Z]/g, "");
    }
    
}
// Event listener to validate inputs on each keyup
function groupNameValidation() {
    const group_nameInput = document.getElementById('txt_group_name');
    group_nameInput.addEventListener('keyup', validate_group);
};
// function to validate add group form ends



// function to validate add family form stats
function validate_family(event) {

    const family_nameInput = event.target;
    const family_name = family_nameInput.value;
    const pattern = /^[a-zA-Z\s]+$/; // Allows only specified characters

    // Check if password contains only allowed characters
    if (!pattern.test(family_name)) {
        family_nameInput.value = family_name.replace(/[^a-zA-Z]/g, "");
    }
}
// Event listener to validate inputs on each keyup
function familyNameValidation() {
    const family_nameInput = document.getElementById('txt_family_name');
    family_nameInput.addEventListener('keyup', validate_family);
};
// function to validate add family form



// function to validate title in add bulletine form starts
function validate_title(event) {

    const bulletin_titleInput = event.target;
    const bulletin_title = bulletin_titleInput.value;
    const pattern = /^[a-zA-Z\s]+$/; // Allows only specified characters
    // Check if password contains only allowed characters
    if (!pattern.test(bulletin_title)) {
        bulletin_titleInput.value = bulletin_title.replace(/[^a-zA-Z]/g, "");
    }
}
// Event listener to validate first_name on each keyup
function titleValidation() {
    const bulletin_titleInput = document.getElementById('txt_title');
    bulletin_titleInput.addEventListener('keyup', validate_title);
};
// function to validate title in add bulletin form ends



// function to validate add profile form starts
function validate_profile_name(event) {

    const profile_nameInput = event.target;
    const profile_name = profile_nameInput.value;
    const pattern = /^[a-zA-Z\s]+$/; // Allows only specified characters

    // Check if password contains only allowed characters
    if (!pattern.test(profile_name)) {
        profile_nameInput.value = profile_name.replace(/[^a-zA-Z]/g, "");

    }
    
}
// Event listener to validate first_name on each keyup
function profileNameValidation() {
    const profile_nameInput = document.getElementById('txt_name');
    profile_nameInput.addEventListener('keyup', validate_profile_name);
};
// function to validate add profile ends



// function to validate add profile form starts
function validate_group_name(event) {

    const profile_nameInput = event.target;
    const profile_name = profile_nameInput.value;
    const pattern = /^[a-zA-Z\s]+$/; // Allows only specified characters

    // Check if password contains only allowed characters
    if (!pattern.test(profile_name)) {
        profile_nameInput.value = profile_name.replace(/[^a-zA-Z]/g, "");

    }
    
}
// Event listener to validate first_name on each keyup
function group_name_validate() {
    const profile_nameInput = document.getElementById('txt_group_name');
    profile_nameInput.addEventListener('keyup', validate_group_name);
};
// function to validate add profile ends