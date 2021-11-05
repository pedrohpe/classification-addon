<div><img src={{$badge.image_url}} width = "200" height= "200"/></div>

<label id="assertion-name-label" for="assertion-name">
    <b>Título:</b>
</label>

{{$badge.name}}

<br />

<label id="assertion-description-label" for="assertion-description">
    <b>Descrição:</b>
</label>

{{$badge.description}}

<br />

<label id="assertion-evidence-label" for="assertion-evidence">
    <b>Evidência:</b>
</label>

{{$badge.evidence}}

<br />

<label id="assertion-evidence-label" for="assertion-evidence">
    <b>Recebido de:</b>
</label>

<a href= {{$badge.issuer_url}}> {{$badge.issuer}} </a>

<br />

<label id="assertion-time-label" for="assertion-time">
    <b>Recebido em:</b>
</label>
 
{{$badge.issued_date}}

<br />

<hr />