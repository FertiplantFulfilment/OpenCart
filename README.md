# OpenCart
Fertiplant Fulfilment officiële extensie voor OpenCart

<h1>Installatie</h1>
<ul>
	<li>Download <a href="https://github.com/vqmod/vqmod" target="_blank">vQmod</a> en volg hun <a href="https://github.com/vqmod/vqmod/wiki" target="_blank">Wiki</a> om het te installeren.</li>
	<li>Download de .zip voor de nieuwste Fertiplant Fulfilment release.
		<ul>
			<li>Voor opencart 2.x download je de<strong>fertiplantfulfilment-opencart-2.x map</strong></li>
			<li>Voor opencart 3.x download je de<strong>fertiplantfulfilment-opencart-3.x map</strong></li>
		</ul>
	</li>
	<li>Fertiplant Fulfilment module installeren
		<ul>
			<li>Upload alle bestanden uit de "upload" folder in de .zip naar de root folder van uw OpenCart webshop.</li>
			<li>Ga naar uw OpenCart AdminPanel</li>
			<li>Ga in het menu naar Extentions > Extensions > Modules</li>
			<li>Zoek naar "Fertiplant Fulfilment" en installeer de module</li>
			<li>Klik nu op "edit" en voer uw persoonlijke Bearer in en druk op "opslaan"</li>
		</ul>
	</li>
</ul>

<h1>Cronjobs</h1>
<p>
	Ons assortiment werkt met realtime producten die gebonden zijn aan beschikbaarheid, seizoen en versheid. Het kan dan ook voorkomen dat een product niet meer beschikbaar is. Daarnaast is het mogelijk dat content verandert. Hiervoor stellen wij verschillende cronjobs in die ervoor zorgen dat u altijd de laatste informatie heeft. Ook voor het inschieten van orders draaien wij een cronjob. Als er een product besteld is in uw webshop die van Fertiplant Fulfilment is wordt dit door de cronjob herkend en in ons systeem ingeschoten. Hieronder vindt u de referentie naar de files. Wij raden aan om de cronjobs om het uur te draaien.
</p>
<ul>
	<li>Cronjobs instellen
		<ul>
			<li>/crons/cronjob.php</li>
			<li>/crons/check-quantity.php</li>
			<li>/crons/check-image.php</li>
			<li>/crons/check-order.php</li>
		</ul>
	</li>
</ul>


