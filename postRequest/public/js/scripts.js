const userBtn = $("#user");
const listUser = $(".container-users ul");
// const input = document.querySelector("input")
// const form = document.querySelector('form')

//const { json } = require("body-parser")

// async function load(name ,url ,action){
//     let res = ''
//     ul.innerHTML = "";
//     if(action == 'del'){
//             res = await fetch(`http://localhost:4000?name=${name}&url=${url}&del=1`).then((data) => data.json())
//     }else if(action == 'add'){
//             res = await fetch(`http://localhost:4000?name=${name}&url=${url}`).then((data) => data.json())

//     }else {
//         res = await fetch("http://localhost:4000").then((data) => data.json())
//     }

//     res.urls.map(url => addElement(url))
// }
// load()

//envio dos dados do usuario para o back
document.userRegister.onsubmit = async (e) => {
  e.preventDefault();

  const form = e.target; //elemnto que disparou o evento
  const data = new FormData(form); //pega todos os dados pelo name
  data.append("novoDado", "true"); //adiciona dados fora do form

  const options = {
    method: form.method,
    body: new URLSearchParams(data),
  };

  //   fetch('http://localhost:4500/register',options).then(resp => resp.json()).then(json =>{
  //       console.log(json)})
  //       .catch(e => {console.log(e)}) //tratar o erro no feth

  try {
    const resp = await fetch("http://localhost:4500/register", options); //tratando no async
    const json = await resp.json();
    console.log(JSON.stringify(json));
  } catch (e) {
    console.log(e);
  }
};

//**fazer verficação ao clicar e estiver carregada não fazer varias soliciações ao banco
userBtn.click(async () => {
  try {
    const resp = await fetch("http://localhost:4500/users"); //tratando no async
    const json = await resp.json();
    listUser.html("");
    json.urls.map((user) => loadUser(user));

    const user = $(".user");
    showUser(user);

    $(".user").click(function(){
        var id = $(this).attr('data-id')
        dataUser(id)

        $('.container-users').addClass('cai-fora') 
        setTimeout(() => {
             $('.container-users').addClass('none')
          }, 1000);

          $('.info-user').removeClass('none')
          setTimeout(() => {
            $('.info-user').addClass('info-user-show')
         }, 300);
    })

  } catch (e) {
    console.log(e);
  }
});

const loadUser = (ele) => {
  listUser.prepend(`<li data-id="${ele.id}" class="user"><span>${ele.id}</span>${ele.name}</li>`);
};

const showUser = (user) => {
  var tempo = 0;
  $(user).each((index, ele) => {
    setTimeout(() => {
      $(ele).addClass("showUser");
    }, tempo);
    tempo += 250;
  });
};

$('.close-user').click(()=>{

     $('.container-users').removeClass('none')
    setTimeout(() => {
         $('.container-users').removeClass('cai-fora')
      }, 500);


         $('.info-user').removeClass('info-user-show')
          setTimeout(() => {
         $('.info-user').addClass('none')   
         }, 500);

})

const dataUser = async (id) => {
    try {
        const resp = await fetch(`http://localhost:4500/user?id=${id}`); //tratando no async
        const json = await resp.json();
        console.log(json)
    
      } catch (e) {
        console.log(e);
      }

}


function addElement({ name, url }) {
  const li = document.createElement("li");
  const a = document.createElement("a");
  const trash = document.createElement("span");

  a.href = url;
  a.innerHTML = name;
  a.target = "_blank";

  trash.innerHTML = "x";
  trash.setAttribute("data-name", name);
  trash.setAttribute("data-url", url);
  trash.onclick = () => removeElement(trash);

  li.append(a);
  li.append(trash);
  ul.appendChild(li);
}

function removeElement(el) {
  if (confirm("Tem certeza que deseja deletar?"))
    var name = el.getAttribute("data-name");
  var url = el.getAttribute("data-url");
  load(name, url, "del");
}

// form.addEventListener("submit", (event) => {
//     event.preventDefault();

//     let { value } = input

//     if (!value)
//         return alert('Preencha o campo')

//     const [name, url] = value.split(",")

//     if (!url)
//         return alert('formate o texto da maneira correta')

//     if (!/^http/.test(url))
//         return alert("Digite a url da maneira correta")

//         load(name, url, 'add')

//     input.value = ""
// })
