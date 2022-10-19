// $('#submit-html').click( () => {
//     $('#image-content').html($('#html-content').val())
// })


const create = () => {
    const imageContainer = document.querySelector('#image-container')
    imageContainer.innerHTML =  document.querySelector('#html-content').value
    document.querySelector('#width').value = imageContainer.clientWidth
    document.querySelector('#height').value = imageContainer.clientHeight
}
