$('#analysis').click((e) =>{
    e.preventDefault();

    try {

        clickBtnCollisionAnalysis()

    } catch (error) {
        console.log(e)
        noResult()
    }
    
})

$('input').change(function(){

    if($(this).hasClass('error-input')){
      $(this).removeClass('error-input')  
      $("label[for='"+$(this).attr('id')+"']").removeClass('error-label')
    } 
})

$('#order-colidence').change(function(){
    const order = $("#order-colidence option:selected").val()
    const orderClass = JSON.parse(sessionStorage.getItem("collisionSeach")); 
    console.log(orderClass)
    orderClass.sort(function(a, b){

        return (a.listaClasseNice ? a.listaClasseNice.codigo : 1) < (b.listaClasseNice ? b.listaClasseNice.codigo : 1);
    
    });

    if(order == 1)  showResult(JSON.parse(sessionStorage.getItem("collisionSeach")))
       else showResult(orderClass)
    
})

$('#accessory').change(function(){

    if($(this).hasClass('error-input')){
      $('#brand').removeClass('error-input')  
      $('label[for="brand"]').removeClass('error-label')
    } 
})

 //animação dos inputs e buscade analise colidênce 
const clickBtnCollisionAnalysis = async () =>{

    const STEP  = $('#step').val()

    if(STEP == 1){
    
        const BRAND = $('#brand')
        const ACCESS = $('#accessory')

        if(!/[a-zA-Z0-9]+/g.test(BRAND.val())){
           BRAND.addClass('error-input') 
           $('label[for="brand"]').addClass('error-label')
           return 
        }
        
        if(ACCESS.val().length && !/[a-zA-Z]/g.test(ACCESS.val())){
            ACCESS.addClass('error-input') 
            $('label[for="accessory"]').addClass('error-label')
            return 
        }

        if($('#brand').hasClass('error-input')) $('#brand').removeClass('error-input')

        $('#one').addClass('block-left')
        $('#one').removeClass('block-center')
    
        $('#two').removeClass('block-right')
        $('#two').addClass('block-center')
        
        $('.msg-btn-one').removeClass('item-center')
        $('.msg-btn-one').addClass('item-left')
        $('.msg-btn-two').addClass('item-center')
        $('.msg-btn-two').removeClass('item-right')

        $('#step').val(2)
    }

    if(STEP == 2){
        
        const CLASSES = $('#classes')

        if(CLASSES.val().length && !/^[0-9,]*$/.test(CLASSES.val())){
            CLASSES.addClass('error-input') 
            $('label[for="classes"]').addClass('error-label')
            return 
         }

        const eventum = await searchCollision()

        if(!eventum.length) return noResult()
        
        $('#result-count').html(eventum.length)
        await showResult(eventum)

        $('#step-one').addClass('block-left')
        $('#step-one').removeClass('block-center')

        $('#step-three').removeClass('block-right')
        $('#step-three').addClass('block-center')

        
    }

}
 //--========================--

const executeFetch = async (param, datas = '',method = 'GET') =>{

    const options = {
      method: method,
      headers: {
        'Content-type': 'application/json'
      },
    };
  
    if(method !== 'GET') options.body = JSON.stringify(datas);
    const eventum = await fetch(`https://0693-2804-d57-6393-300-1623-ca14-b0d1-3322.sa.ngrok.io/api/v1/${param}`, options)
                      .then(req => req)
                      .then(req => req.json())
                      .catch((e) => {
                                      console.log('ERROR: ',e)
                                      return []   
                                    });

    return eventum;
}

const searchCollision = async () => {
    const DATA = {
        brand:"",
        accessory:"",
        class:[]
    }

    DATA.brand  =  $('#brand').val()
    DATA.accessory  = $('#accessory').val() ?? ""
    DATA.class = $('#classes').val() ? $('#classes').val().split(',') : []
    
    const eventum = await executeFetch('colidence/search',DATA,'POST')
    const jsonEventum =  JSON.stringify(eventum)
    sessionStorage.setItem("collisionSeach", jsonEventum);

    return eventum
}
        
const showResult = async (e) => {
  $('#showResult').html('')
   await  e.map((e,i) => {
     
         $('#showResult').append(
                `<div class="item" data-id="${i}">
                        <div class="item-content">
                            <span>
                                <input type="checkbox" id="${i}" onchange="removeItemCollision(this)" data-id="${i}">
                            </span>
                            <label for="${i}" class="">
                                <p class="item-classes">  ${e.listaClasseNice ? "Classe "+e.listaClasseNice.codigo+" - " : ""}  Processo ${e.numero}</p>
                                    <p class="item-name"><b>${e.marca.nome}</b></p>
                                    <p class="item-description">${e.despacho.nome}</p>
                            </label>
                        </div>
                    </div>`)
           })
     
    await $('.content-panel').addClass('block-left')
    await $('.content-panel-result').addClass('block-center')
    
}

//se não achar resultado de conlidence
const noResult = () =>{

    $('#step-one').addClass('block-left')
    $('#step-one').removeClass('block-center')
    
    $('#step-three .content-step').addClass('none')
    $('#no-results').removeClass('none')
    $('#step-three').removeClass('block-right')
    $('#step-three').addClass('block-center')
 }
 //

 //voltar a pesquisar
 $('.new-analysis').click((e) =>{
    e.preventDefault();

    $('#one').removeClass('block-left')
    $('#one').addClass('block-center')

    $('#two').removeClass('block-center')
    $('#two').removeClass('block-left')
    $('#two').addClass('block-right')
    
    $('.msg-btn-one').addClass('item-center')
    $('.msg-btn-one').removeClass('item-left')
    $('.msg-btn-two').removeClass('item-center')
    $('.msg-btn-two').addClass('item-right')

    /*-==================================-*/

    $('#step-one').removeClass('block-left')
    $('#step-one').addClass('block-center')

    $('#step-three').addClass('block-right')
    $('#step-three').removeClass('block-center')

    /*-==================================-*/

    setTimeout(() => {
        $('#step-three .content-step').removeClass('none')
        $('#no-results').addClass('none')
        $('#step-three').addClass('block-right')
        $('#step-three').removeClass('block-center')
      }, 700);

    $('#step').val(1)

    /*-==================================-*/

    $('.content-panel').removeClass('block-left')
    $('.content-panel-result').removeClass('block-center')
})
//

//voltar ao passo 2
$('#back-step-two').click(() =>{

    $('#step-one').removeClass('block-left')
    $('#step-one').addClass('block-center')

    $('#step-three').addClass('block-right')
    $('#step-three').removeClass('block-center')

    /*-==================================-*/
    
    $('#step').val(2)

    setTimeout(() => {
        $('#step-three .content-step').removeClass('none')
        $('#no-results').addClass('none')
        $('#step-three').addClass('block-right')
        $('#step-three').removeClass('block-center')
      }, 700);
    
    /*-==================================-*/

    $('.content-panel').removeClass('block-left')
    $('.content-panel-result').removeClass('block-center')
    $('#showResult').html('')
})
//

//remove item de pesquisa colidence
UNDO = []

const removeItemCollision = (e) => {
    
    var id = $(e).attr('data-id')
    console.log(id)
    if (!$(e).prop("checked")) return
    
    setTimeout(() => {
        UNDO[id] =  true
        $(e).closest('.item-content').addClass('none')
        $(e).closest('.item').prepend(`<p class="undo-action" onclick="undoAction(this)"><u>Desfazer ação</u></p>`)
        removeItem($(e).closest('.item'))
    }, 1000);
}

const undoAction = (e) =>{

   id = $(e).closest('.item').attr('data-id')
   UNDO[id] = null
   $(e).closest('.item').find('.item-content').removeClass('none')
   $(e).closest('.item').find('.undo-action').remove()
}

const removeItem = (e) =>{

    $(e).find('input').prop("checked",false) //desmarcar input para não retornar marcado

    setTimeout(() => {
      id = $(e).attr('data-id')
      

      if(!UNDO[id]) return

      e.remove()
    }, 2500);
 }

 //--========================--



 



  