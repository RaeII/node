const ul = document.querySelector(".url-elements")
const input = document.querySelector("input")
const form = document.querySelector('form')

async function load(name ,url ,action){
    let res = ''
    ul.innerHTML = "";
    if(action == 'del'){
            res = await fetch(`http://localhost:4000?name=${name}&url=${url}&del=1`).then((data) => data.json())
    }else{
            res = await fetch("http://localhost:4000").then((data) => data.json())
    }
                      
    res.urls.map(url => addElement(url))
}
load()


function addElement({ name, url }) {
    const li = document.createElement('li')
    const a = document.createElement("a")
    const trash = document.createElement("span")
    
    

    a.href = url
    a.innerHTML = name
    a.target = "_blank"
     
    
    trash.innerHTML = "x"
    trash.setAttribute('data-name',name)
    trash.setAttribute('data-url',url)
    trash.onclick = () => removeElement(trash)
    
    
    li.append(a)
    li.append(trash)
    ul.appendChild(li)

}

function removeElement(el) {
    if (confirm('Tem certeza que deseja deletar?'))
       var name = el.getAttribute('data-name')
       var url = el.getAttribute('data-url')
       load(name, url, 'del')
}

form.addEventListener("submit", (event) => {
    event.preventDefault();

    let { value } = input

    if (!value) 
        return alert('Preencha o campo')

    const [name, url] = value.split(",")

    if (!url) 
        return alert('formate o texto da maneira correta')

    if (!/^http/.test(url)) 
        return alert("Digite a url da maneira correta")

    addElement({ name, url })

    input.value = ""
})