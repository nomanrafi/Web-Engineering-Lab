function validateNumberOnly(input) {
    const value = input.value;
    const numberRegex = /^\d*$/;
    input.classList.remove('valid', 'invalid');
    if (value === '') {
        return;
    }
    if (numberRegex.test(value)) {
        input.classList.add('valid');
    } else {
        input.classList.add('invalid');
    }
}

function validateTextOnly(input) {
    const value = input.value;
    const textRegex = /^[A-Za-z\s.]*$/;
    input.classList.remove('valid', 'invalid');
    if (value === '') {
        return;
    }
    if (textRegex.test(value)) {
        input.classList.add('valid');
    } else {
        input.classList.add('invalid');
    }
}

function validateMixed(input) {
    const value = input.value;
    input.classList.remove('valid', 'invalid');
    if (value === '') {
        input.classList.add('invalid');
    } else {
        input.classList.add('valid');
    }
}

function validateHeight(input) {
    const value = input.value;
    const heightRegex = /^[A-Za-z0-9\s."]*$/;
    input.classList.remove('valid', 'invalid');
    if (value === '') {
        return;
    }
    if (heightRegex.test(value)) {
        input.classList.add('valid');
    } else {
        input.classList.add('invalid');
    }
}

function validateAgeRange(input) {
    const value = input.value;
    const ageRangeRegex = /^\d*-\d*$/;
    input.classList.remove('valid', 'invalid');
    if (value === '') {
        return;
    }
    if (ageRangeRegex.test(value)) {
        input.classList.add('valid');
    } else {
        input.classList.add('invalid');
    }
}

function validateSelect(input) {
    const value = input.value;
    input.classList.remove('valid', 'invalid');
    if (value === '' || value === 'Select' || value === 'Select number of siblings' || value === 'Select your annual income' || value === 'Select your complexion' || value === 'Select your body type' || value === 'Select your diet preference' || value === 'Select if applicable') {
        input.classList.add('invalid');
    } else {
        input.classList.add('valid');
    }
}

function validateForm(event) {
    event.preventDefault();
    let isValid = true;

    // Number-only fields
    const numberFields = [
        { id: 'age', element: document.getElementById('age') },
        { id: 'contact', element: document.getElementById('contact') }
    ];
    const numberRegex = /^\d+$/;

    numberFields.forEach(field => {
        const value = field.element.value.trim();
        if (value === '' || !numberRegex.test(value)) {
            field.element.classList.add('invalid');
            field.element.classList.remove('valid');
            isValid = false;
        } else {
            field.element.classList.add('valid');
            field.element.classList.remove('invalid');
        }
    });

    // Text-only fields (letters, spaces, dots)
    const textFields = [
        { id: 'name', element: document.getElementById('name') },
        { id: 'placeOfBirth', element: document.getElementById('placeOfBirth') },
        { id: 'religion', element: document.getElementById('religion') },
        { id: 'nationality', element: document.getElementById('nationality') },
        { id: 'fatherName', element: document.getElementById('fatherName') },
        { id: 'fatherOccupation', element: document.getElementById('fatherOccupation') },
        { id: 'motherName', element: document.getElementById('motherName') },
        { id: 'motherOccupation', element: document.getElementById('motherOccupation') },
        { id: 'highestQualification', element: document.getElementById('highestQualification') },
        { id: 'university', element: document.getElementById('university') },
        { id: 'additionalCertifications', element: document.getElementById('additionalCertifications') },
        { id: 'occupation', element: document.getElementById('occupation') },
        { id: 'futureCareerPlan', element: document.getElementById('futureCareerPlan') },
        { id: 'partnerEducation', element: document.getElementById('partnerEducation') },
        { id: 'partnerOccupation', element: document.getElementById('partnerOccupation') },
        { id: 'partnerReligion', element: document.getElementById('partnerReligion') },
        { id: 'aboutMe', element: document.getElementById('aboutMe') },
        { id: 'futurePlans', element: document.getElementById('futurePlans') },
        { id: 'hobbies', element: document.getElementById('hobbies') }
    ];
    const textRegex = /^[A-Za-z\s.]+$/;

    textFields.forEach(field => {
        const value = field.element.value.trim();
        if (value === '' || !textRegex.test(value)) {
            field.element.classList.add('invalid');
            field.element.classList.remove('valid');
            isValid = false;
        } else {
            field.element.classList.add('valid');
            field.element.classList.remove('invalid');
        }
    });

    // Height fields
    const heightFields = [
        { id: 'height', element: document.getElementById('height') },
        { id: 'partnerHeight', element: document.getElementById('partnerHeight') }
    ];
    const heightRegex = /^[A-Za-z0-9\s."]+$/;

    heightFields.forEach(field => {
        const value = field.element.value.trim();
        if (value === '' || !heightRegex.test(value)) {
            field.element.classList.add('invalid');
            field.element.classList.remove('valid');
            isValid = false;
        } else {
            field.element.classList.add('valid');
            field.element.classList.remove('invalid');
        }
    });

    // Age Range field
    const ageRangeField = document.getElementById('partnerAge');
    const ageRangeValue = ageRangeField.value.trim();
    const ageRangeRegex = /^\d+-\d+$/;
    if (ageRangeValue === '' || !ageRangeRegex.test(ageRangeValue)) {
        ageRangeField.classList.add('invalid');
        ageRangeField.classList.remove('valid');
        isValid = false;
    } else {
        ageRangeField.classList.add('valid');
        ageRangeField.classList.remove('invalid');
    }

    // Mixed fields
    const mixedFields = [
        { id: 'dob', element: document.getElementById('dob') },
        { id: 'email', element: document.getElementById('email') },
        { id: 'permanentAddress', element: document.getElementById('permanentAddress') },
        { id: 'presentAddress', element: document.getElementById('presentAddress') },
        { id: 'comments', element: document.getElementById('comments') }
    ];

    mixedFields.forEach(field => {
        const value = field.element.value.trim();
        if (value === '') {
            field.element.classList.add('invalid');
            field.element.classList.remove('valid');
            isValid = false;
        } else {
            field.element.classList.add('valid');
            field.element.classList.remove('invalid');
        }
    });

    // Select fields (non-empty validation)
    const selectFields = [
        { id: 'maritalStatus', element: document.getElementById('maritalStatus') },
        { id: 'bloodGroup', element: document.getElementById('bloodGroup') },
        { id: 'siblings', element: document.getElementById('siblings') },
        { id: 'income', element: document.getElementById('income') },
        { id: 'complexion', element: document.getElementById('complexion') },
        { id: 'bodyType', element: document.getElementById('bodyType') },
        { id: 'diet', element: document.getElementById('diet') },
        { id: 'smoking', element: document.getElementById('smoking') },
        { id: 'drinking', element: document.getElementById('drinking') },
        { id: 'disability', element: document.getElementById('disability') }
    ];

    selectFields.forEach(field => {
        const value = field.element.value.trim();
        if (value === '' || value === 'Select' || value === 'Select number of siblings' || value === 'Select your annual income' || value === 'Select your complexion' || value === 'Select your body type' || value === 'Select your diet preference' || value === 'Select if applicable') {
            field.element.classList.add('invalid');
            field.element.classList.remove('valid');
            isValid = false;
        } else {
            field.element.classList.add('valid');
            field.element.classList.remove('invalid');
        }
    });

    // Radio button validation
    const genderRadios = document.getElementsByName('gender');
    let genderSelected = false;
    genderRadios.forEach(radio => {
        if (radio.checked) {
            genderSelected = true;
        }
    });
    const genderContainer = document.querySelector('.radio-group');
    if (!genderSelected) {
        genderContainer.classList.add('invalid');
        isValid = false;
    } else {
        genderContainer.classList.remove('invalid');
    }

    if (isValid) {
        alert('Form submitted successfully!');
        // Add form submission logic here
    } else {
        alert('Please correct the errors in the form.');
    }

    return isValid;
}