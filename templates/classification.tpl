<h3>{{$title}}</h3>

<br />

<div class = "">
    {{if $badges}}
    {{foreach $badges as $badge}}
    {{include file="addon/classification/templates/badge.tpl"}}
    {{/foreach}}
    {{else}}
    <b> Sem badges presentes. </b>
    {{/if}}

</div>

<br>
