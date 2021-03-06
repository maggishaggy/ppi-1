<!DOCTYPE html>
<html>
<head>
<title>interacto.me: visualization for protein interaction networks</title>
<meta http-equiv="Content-type" content="text/html; charset=UTF-8" />
<meta http-equiv="Content-Language" content="en-us" />
<meta http-equiv="Content-Script-Type" content="text/javascript">
<meta name="description" content="Build your own protein interaction network!" />
<meta name="keywords" content="PPI,PIN,graph,protein,interaction,network,sigma,sigmajs,Gephi,layout" />
<meta name="author" content="Jack Peterson" />
<link href="css/ppi.css" rel="stylesheet" type="text/css" />
<link href="http://code.jquery.com/ui/1.9.1/themes/base/jquery-ui.css" rel="stylesheet" type="text/css" />
<link href="css/bootstrap.min.css" rel="stylesheet" type="text/css" />
<link href="css/bootstrap-responsive.min.css" rel="stylesheet" type="text/css" />
<script src="http://code.jquery.com/jquery-1.9.1.min.js"></script>
<script src="http://code.jquery.com/ui/1.9.1/jquery-ui.js"></script>
<script src="http://code.jquery.com/jquery-migrate-1.1.1.min.js"></script>
<script src="js/jquery.lightbox_me.js"></script>
<script src="js/sigma.min.js"></script>
<script src="js/sigma.parseJson.js"></script>
<script>
var organism = '<?php echo $org; ?>';
var dataset = '<?php echo $dataset; ?>';
var showIntro = '<?php echo (!isset($_GET["ppi"])); ?>';

$(document).ready(function() {

	/**
	* Lightboxes (lightbox.me) for 'about' and 'intro' buttons on navigation
	* bar.  The 'intro' lightbox is shown by default when the user first comes
	* to interacto.me, and provides basic use instructions for the site.
	*/
	$('#try-1').click(function(e) {
		$('#about_this_site').lightbox_me({
			centered: true, 
			onLoad: function() { 
				$('#about_this_site').find('input:first').focus()
			}
		});
		e.preventDefault();
	});
	if (showIntro) {
		$('#intro').lightbox_me({centered: true});
	}
		
	/**
	* Use jQuery post() to fetch the response to protein search queries.  The
	* search query is sent via POST, and organism name (ppi) and dataset (d)
	* are sent via GET.  AJAX response is sent to the search_results div.
	*/
	$('#protein_lookup').submit(function() {
		$.post('index.php?ppi=' + organism + '&d=' + dataset, 
			$(this).serialize(), function(response) {
				$('#search_results').html(response).show();
			}
		);
		return false;
	});	
});

/**
* Initialize the sigma.js PPI graph, and populate it with nodes and edges
* according to the organism and dataset the user selects.  Default is the
* small-scale dataset from S. cerevisiae.  Graph data are stored (in JSON 
* format) in the data/ directory.  (parseJson.js is required to load these
* data into the graph.)
*/
function init() {
	
	var numEdges = <?php echo $summary['edges']; ?>;
	var edgeType = (numEdges < 25000) ? 'curve' : 'line';

	// Instantiate sigma.js and customize rendering
	var sigInst = sigma.init(document.getElementById('sigma-example')).drawingProperties({
		defaultLabelColor: '#fff',
		defaultLabelSize: 14,
		defaultLabelBGColor: '#fff',
		defaultLabelHoverColor: '#000',
		labelThreshold: 100,
		defaultEdgeType: edgeType
	}).graphProperties({
		minNodeSize: 0.5,
		maxNodeSize: 7,
		minEdgeSize: 1,
		maxEdgeSize: 1
	}).mouseProperties({
		maxRatio: 4
	});
 
	// Parse JSON file to populate the graph
	sigInst.parseJson('data/' + organism + '_' + dataset + '.json', 
		function() { 
			sigInst.draw();
		}
	);
	
	// Bind events
	var greyColor = '#ccc';
	sigInst.bind('overnodes',function(event){
		var nodes = event.content;
		var neighbors = {};
		sigInst.iterEdges(function(e){
			if(nodes.indexOf(e.source)<0 && nodes.indexOf(e.target)<0){
				if(!e.attr['grey']){
					e.attr['true_color'] = e.color;
					e.color = greyColor;
					e.attr['grey'] = 1;
				}
			}
			else{
				e.color = e.attr['grey'] ? e.attr['true_color'] : e.color;
				e.attr['grey'] = 0;
				 
				neighbors[e.source] = 1;
				neighbors[e.target] = 1;
			}
		}).iterNodes(function(n){
			if(!neighbors[n.id]){
				if(!n.attr['grey']){
					n.attr['true_color'] = n.color;
					n.color = greyColor;
					n.attr['grey'] = 1;
				}
			}
			else{
				n.color = n.attr['grey'] ? n.attr['true_color'] : n.color;
				n.attr['grey'] = 0;
			}
		}).draw(2,2,2);
	}).bind('outnodes',function(){
		sigInst.iterEdges(function(e){
			e.color = e.attr['grey'] ? e.attr['true_color'] : e.color;
			e.attr['grey'] = 0;
		}).iterNodes(function(n){
			n.color = n.attr['grey'] ? n.attr['true_color'] : n.color;
			n.attr['grey'] = 0;
		}).draw(2,2,2);
	}).bind('downnodes', function(event) {
		var clickNode = event.content[0];
		sigInst.iterNodes(function(n){
			node = n;
			$.post('index.php?ppi=' + organism + '&d=' + dataset, 
				{'protein_id': clickNode}, function(response) {
					sigInst.zoomTo(node.displayX,node.displayY,12);
					$('#search_results').html(response).show();
				}
			);
		},[event.content[0]]);
	}).draw(2,2,2);
		
	// Draw the graph
	sigInst.draw();
}

if (document.addEventListener) {
	document.addEventListener('DOMContentLoaded', init, false);
}
else {
	window.onload = init;
}
</script>
<script type="text/javascript">
  var _gaq = _gaq || [];
  _gaq.push(['_setAccount', 'UA-39726053-1']);
  _gaq.push(['_trackPageview']);
  (function() {
    var ga = document.createElement('script'); ga.type = 'text/javascript'; ga.async = true;
    ga.src = ('https:' == document.location.protocol ? 'https://ssl' : 'http://www') + '.google-analytics.com/ga.js';
    var s = document.getElementsByTagName('script')[0]; s.parentNode.insertBefore(ga, s);
  })();
</script>
</head>

<body>
<div class="wrapper">
	<?php include 'view/navbar.html'; ?>
	<br />
	<div class="span12 sigma-parent" id="sigma-example-parent">
		<div class="sigma-expand" id="sigma-example"></div>
	</div>
	<div id="leftbar">
	<table>
		<tr>
		<td id="sce"><a href="index.php?ppi=sce">budding yeast</a></td>
		<td id="dme"><a href="index.php?ppi=dme">fruit fly</a></td>
		<td id="hsa"><a href="index.php?ppi=hsa">human</a></td>
		</tr>
		<tr>
		<td id="spo"><a href="index.php?ppi=spo">fission yeast</a></td>
		<td id="rno"><a href="index.php?ppi=rno">rat</a></td>
		<td id="mmu"><a href="index.php?ppi=mmu">mouse</a></td>
		</tr>
		<tr>
		<td id="ath"><a href="index.php?ppi=ath">arabidopsis</a></td>
		<td id="cel"><a href="index.php?ppi=cel">worm</a></td>
		<td id="eco"><a href="index.php?ppi=eco">E. coli</a></td>
		</tr>
	</table>
	</div>
	<div id="datapicker">
	<table>
		<tr>
		<td id="ss"><a href="index.php?ppi=<?php echo $org; ?>&d=ss">small-scale</a></td>
		<td id="hc"><a href="index.php?ppi=<?php echo $org; ?>&d=hc">hi-confidence</a></td>
		</tr>
	</table>
	</div>
	<script>
	$('#<?php echo $org; ?>').css('background-color', '#ffcccc');
	$('#<?php echo $dataset; ?>').css('background-color', '#ffcccc');
	</script>
	<?php
	echo "
	<div id='summary'>
		<table>
			<tr><th><a href='" . $summary['url'] . "'>" . $summary['common'] . " <i>(" . $summary['name'] . ")</a></i></th></tr>
			<tr><td>proteins: " . $summary['nodes'] . "</td></tr>
			<tr><td>interactions: " . $summary['edges'] . "</td></tr>
			<tr><td>average degree: " . $summary['average_k'] . "</td></tr>
			<tr><td>clustering coefficient: " . $summary['clustering'] . "</td></tr>
			<tr><td>modularity: " . $summary['modularity'] . "</td></tr>
			<tr><td>components: " . $summary['component'] . "</td></tr>
			<tr><td>diameter: " . $summary['diameter'] . "</td></tr>
			<tr><td>average path length: " . $summary['average_l'] . "</td></tr>
			</tr>
		</table>
	</div>
	";
	?>
	<div id="searchbar">
		<form method="post" class="form" id="protein_lookup">
			<table>
				<tr>
				<td><input id="lookup" class="text_input" type="text" name="lookup" size="10" required="required" placeholder="Search for proteins or genes..." /></td>
				</tr>
				<tr>
				<td><input class="button" type="submit" value="Search" /></td>
				</tr>
			</table>
		</form>
	</div>
	<div id="search_results"></div>
	<script>
	$(window).load(function () {
		$('#search_results').css('right', $('#rightbar').width() + 20);
	});
	$(window).resize(function () {
		$('#search_results').css('right', $('#rightbar').width() + 20);
	});
	</script>
	<div id="infobox"></div>
	<div id="rightbar">
		<table>
			<tr><th>datasets</th></tr>
			<tr><td><span class="hover-item"><a href="http://thebiogrid.org/">BioGRID</a>
				<span>physical and genetic interactions</span>
			</span></td></tr>
			<tr><td><span class="hover-item"><a href="http://hintdb.hgc.jp/htp/index.html">HitPredict</a>
				<span>small-scale 'high-confidence' data, and predicted interactions based on Bayesian inference</span>
			</span></td></tr>
			<tr><td><span class="hover-item"><a href="http://www.uniprot.org/">UniProt</a>
				<span>comprehensive and freely accessible resource of protein sequence and functional information</span>
			</span></td></tr>
			<tr><td><span class="hover-item"><a href="http://string-db.org/">STRING</a>
				<span>database of known and predicted protein interactions, includes direct (physical) and indirect (functional) associations</span>
			</span></td></tr>
			<tr><td><span class="hover-item"><a href="https://interfly.med.harvard.edu/">DPiM</a>
				<span>protein interaction map of the Drosophila melanogaster proteome</span>
			</span></td></tr>
			<tr><td><span class="hover-item"><a href="http://www.ebi.ac.uk/intact/">IntAct</a>
				<span>open source database system and analysis tools for molecular interaction data</span>
			</span></td></tr>
			<tr><td><span class="hover-item"><a href="http://mint.bio.uniroma2.it/mint/Welcome.do">MINT</a>
				<span>protein-protein interactions mined from the scientific literature by expert curators</span>
			</span></td></tr>
			<tr><td><span class="hover-item"><a href="http://dip.doe-mbi.ucla.edu/dip/Main.cgi">DIP</a>
				<span>experimentally determined interactions between proteins, combining information from a variety of sources</span>
			</span></td></tr>
			<tr><td><span class="hover-item"><a href="http://www.hprd.org/">HPRD</a>
				<span>human protein reference database</span>
			</span></td></tr>
			<tr><td><span class="hover-item"><a href="http://mips.helmholtz-muenchen.de/proj/ppi/">MIPS</a>
				<span>collection of manually curated high-quality PPI data collected from the scientific literature by expert curators</span>
			</span></td></tr>
			<tr><td><span class="hover-item"><a href="http://www.arabidopsis.org/portals/proteome/proteinInteract.jsp">TAIR</a>
				<span>Arabidopsis protein-protein interaction data curated from the literature</span>
			</span></td></tr>
		</table>
		<br />
		<table>
			<tr><th>tools</th></tr>
			<tr><td><span class="hover-item"><a href="http://www.mathworks.com/matlabcentral/fileexchange/10922">MatlabBGL</a>
				<span>very fast graphs package for Matlab</span>
			</span></td></tr>
			<tr><td><span class="hover-item"><a href="https://sites.google.com/site/bctnet/">Brain Connectivity Toolbox</a>
				<span>Matlab scripts to calculate most standard graph-theoretic quantities</span>
			</span></td></tr>
			<tr><td><span class="hover-item"><a href="http://www.cmth.bnl.gov/%7Emaslov/matlab.htm">Sergei Maslov's website</a>
				<span>Matlab scripts for degree-preserving network rewiring and degree-degree correlation maps</span>
			</span></td></tr>
			<tr><td><span class="hover-item"><a href="https://sites.google.com/site/bctnet/Home/functions/modularity_louvain_und.m?attredirects=0">modularity calculator</a>
				<span>Matlab implementation of the Louvain algorithm to calculate network modularity</span>
			</span></td></tr>
			<tr><td><span class="hover-item"><a href="http://cbg.garvan.unsw.edu.au/pina/">PINA</a>
				<span>integrated platform for protein interaction network construction, filtering, analysis, visualization and management</span>
			</span></td></tr>
			<tr><td><span class="hover-item"><a href="http://cytoscape.org">Cytoscape</a>
				<span>open source software platform for visualizing complex networks and integrating these with any type of attribute data</span>
			</span></td></tr>
			<tr><td><span class="hover-item"><a href="http://mips.helmholtz-muenchen.de/genre/proj/mpact">MPact</a>
				<span>common access point to interaction resources at MIPS</span>
			</span></td></tr>
			<tr><td><span class="hover-item"><a href="http://snap.stanford.edu/snap/index.html">SNAP</a>
				<span>general purpose, high performance system for analysis and manipulation of large networks</span>
			</span></td></tr>
			<tr><td><span class="hover-item"><a href="http://snap.stanford.edu/snap/index.html">APID</a>
				<span>Agile Protein Interaction DataAnalyzer: interactive bioinformatic web-tool that has been developed to allow exploration and analysis of main currently known information about protein-protein interactions integrated and unified in a common and comparative platform</span>
			</span></td></tr>
			<tr><td><span class="hover-item"><a href="http://sigmajs.org/">sigma.js</a>
				<span>open-source lightweight JavaScript library to draw graphs</span>
			</span></td></tr>
			<tr><td><span class="hover-item"><a href="https://gephi.org/">Gephi</a>
				<span>interactive visualization and exploration platform for all kinds of networks and complex systems, dynamic and hierarchical graphs</span>
			</span></td></tr>
		</table>
	</div>
	<div class="push"></div>
</div>
<div id="footer">&copy; 2012 - 2013 <a href="http://www.tinybike.net">Jack Peterson</a>.  All rights reserved.</div>

<!--- Lightbox --->
<div id="about_this_site">
	<h3 id="see_id" style="float:left">About this site</h3>
	<div id="close_button">
	<a href="#" onclick="$('#about_this_site').trigger('close'); return false;"><img src="images/close_button.png" alt="close" width="18px" /></a>
	</div>
	<div id="about_this_site_form">
		<p>The <a href="http://dillgroup.org">Dill research group</a>, at <a href="http://www.stonybrook.edu">Stony Brook University's</a> <a href="http://www.laufercenter.org">Laufer Center</a>, has recently begun a computational study of eukaryotic protein-protein interaction (PPI) network evolution.  We published our basic model layout in <i>PLoS ONE</i>, in 2012:</p>
		<p>J. Peterson, S. Presse, K. Peterson, and K. Dill. <a href="http://www.plosone.org/article/info%3Adoi%2F10.1371%2Fjournal.pone.0039052">Simulated evolution of protein-protein interaction networks with realistic topology</a>. <i>PLoS ONE</i> 7(6): e39052, 2012.</p>
		<p>The Matlab scripts we used to carry out our simulations are freely available on <a href="https://github.com/tensorjack/DUNE">GitHub</a>.  (In addition, you will need to install the <a href="http://www.mathworks.com/matlabcentral/fileexchange/10922">MatlabBGL package</a> and the <a href="https://sites.google.com/a/brain-connectivity-toolbox.net/bct/Home/functions/modularity_louvain_und.m?attredirects=0">Louvain modularity script</a>.)  In our model, protein networks evolve by two known biological mechanisms: (1) a gene can duplicate, putting one copy under new selective pressures that allow it to establish new relationships to other proteins in the cell, and (2) a protein undergoes a mutation that causes it to develop new binding or new functional relationships with existing proteins. In addition, we allow for the possibility that once a mutated protein develops a new relationship with another protein (called the target), the mutant protein can also more readily establish relationships with other proteins in the target’s neighborhood.</p>
		<p>The visualizations shown here are generated using <a href='http://sigmajs.org'>sigma.js</a>, with the underlying graph files created by <a href='https://gephi.org'>Gephi</a>.  The layouts are generated using Gephi's ForceAtlas2 algorithm.  The data used here are the small-scale and 'hi-confidence' datasets from <a href='http://hintdb.hgc.jp/htp/index.html'>HitPredict</a>.  (Clicking on an organism name gives a direct link to download the data.)  For the small-scale datasets, both colors and node sizes represent degree (number of interactions).  For the hi-confidence datasets, node sizes represent degree, and the colors partition the graph by modularity class ('communities' of proteins).</p>
		<p>The code for this website is open-source, and is available at <a href="https://github.com/tensorjack/ppi">GitHub</a>.</p>
	</div>
</div>
<div id='intro'>
	<div id="close_button">
	<a href="#" onclick="$('#intro').trigger('close'); return false;"><img src="images/close_button.png" alt="close" width="18px" /></a>
	</div>
	<p><strong>Welcome to <a href="http://interacto.me">interacto.me</a>, an interactive tool for visualizing protein interaction networks!</strong></p>
	<p>To use this tool, first, choose your organism of interest.  Then, push the corresponding button at the top left, to generate the protein-protein interaction networks for that organism.  The panel below those buttons gives you the option for "small-scale" vs. "high-confidence".  'Hi-confidence' uses selected high-throughput experimental data, using the <a href='http://hintdb.hgc.jp/htp/index.html'>HitPredict</a> method.  'Small-scale' excludes data obtained from high-throughput experiments, as these data can be unreliable.</p>
	<p>To drill down deeper, you can explore one protein at a time in two different ways:</p>
	<ol>
	<li><p>Click on a node to get more information about the particular protein it represents.  You'll get a zoomed-in view, plus some basic information about the protein from the UniProt database. You can click on the protein name in the popup box to visit the UniProt page, which has much more detail about the protein, including its amino acid sequence. You can also zoom in using your mouse wheel, and drag the network around on your screen by using the mouse.</p></li>
	<li><p>To search for a particular protein or gene of interest, type the name of the protein or gene (or the UniProt/Swissprot ID), then click 'search' in the box at the upper right. 	For more information about how these networks are generated, click the 'about' button in the upper right hand corner.</p></li>
	</ol>
	<p>(To display this popup again, just click 'help' on the top bar.)</p>
</div>	
<!--- End lightbox --->

</body>
</html>
