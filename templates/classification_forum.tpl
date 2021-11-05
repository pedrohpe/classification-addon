<script>
function getBadgeQuery(){
    var xhr = new XMLHttpRequest();
    var select = document.getElementById("badgetypes");
    var id = select.value;

    var datebegin = document.getElementById("datebegin").value;
    var datefinish = document.getElementById("datefinish").value;

    xhr.onreadystatechange = function(){
        if (this.readyState == 4 && this.status == 200){
            const parser = new DOMParser();
            var innerDoc = parser.parseFromString(xhr.response, "text/html");
            var result = innerDoc.getElementById("inresult");

            document.getElementById("result").innerHTML = result.innerHTML;
        }
    };
    xhr.open("GET", "classification/badgetype/" + id, true);
    xhr.send();

}

</script>

<h3>{{$title}}</h3>

<br />
<form action="classification/post" method="post">
    <div>
        <b> Escolher tipo de badge a ser distribuída: </b>
        <select name="badgetypes" id="badgetypes" onChange="getBadgeQuery();">
            {{if $badgetypes}}
            {{foreach $badgetypes as $badge}}
            <option value="{{$badge.id}}"> {{$badge.name}} </option>
            {{/foreach}}
            {{/if}}
        </select>

        <br />
        <b> Escolha o período da avaliação </b>
        <input id="datebegin" type = "date">
        <input id="datefinish" type = "date">
        <br />

        <input id="buttonclick" type="button" value="Atualizar" onClick="getBadgeQuery();"/>

    </div>

    <div id="result">
    </div>

</form>

<br>
