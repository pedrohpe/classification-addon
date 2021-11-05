
<div id="inresult">
    <div> <img src = {{$badge_receiver.image}}/></div>

    <label id="member-name-label" for="member-name">
        <b>Participante:</b>
    </label>

    {{$badge_receiver.name}}

    <input type="hidden" id="badgereceiver-nick" 
    name="badgereceiver-nick" value="{{$badge_receiver.nick}}">

    <input type="hidden" id="badgereceiver-evidence" name="badgereceiver-evidence" 
    value="{{$badge_receiver.evidence}}">

    <div>
        <input id="buttonclick" type="submit" value="Enviar badge">
    </div> 
</div>

