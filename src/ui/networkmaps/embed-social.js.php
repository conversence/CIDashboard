<?php
/********************************************************************************
 *                                                                              *
 *  (c) Copyright 2015 The Open University UK                                   *
 *                                                                              *
 *  This software is freely distributed in accordance with                      *
 *  the GNU Lesser General Public (LGPL) license, version 3 or later            *
 *  as published by the Free Software Foundation.                               *
 *  For details see LGPL: http://www.fsf.org/licensing/licenses/lgpl.html       *
 *               and GPL: http://www.fsf.org/licensing/licenses/gpl-3.0.html    *
 *                                                                              *
 *  This software is provided by the copyright holders and contributors "as is" *
 *  and any express or implied warranties, including, but not limited to, the   *
 *  implied warranties of merchantability and fitness for a particular purpose  *
 *  are disclaimed. In no event shall the copyright owner or contributors be    *
 *  liable for any direct, indirect, incidental, special, exemplary, or         *
 *  consequential damages (including, but not limited to, procurement of        *
 *  substitute goods or services; loss of use, data, or profits; or business    *
 *  interruption) however caused and on any theory of liability, whether in     *
 *  contract, strict liability, or tort (including negligence or otherwise)     *
 *  arising in any way out of the use of this software, even if advised of the  *
 *  possibility of such damage.                                                 *
 *                                                                              *
 ********************************************************************************/
/**
 * @author Michelle Bachler
 */

header('Content-Type: text/javascript;');
require_once('../../config.php');
?>

var forcedirectedGraph = null;

function loadEmbedSocialNet() {

	$("social-embed-div").innerHTML = "";

	/**** CHECK GRAPH SUPPORTED ****/
	if (!isCanvasSupported()) {
		$("social-embed-div").insert('<div style="float:left;font-weight:12pt;padding:10px;"><?php echo $LNG->GRAPH_NOT_SUPPORTED; ?></div>');
		return;
	}

	/**** SETUP THE GRAPH ****/

	var graphDiv = new Element('div', {'id':'graphUserDiv', 'style': 'clear:both;float:left'});
	var width = 4000;
	var height = 4000;

	var messagearea = new Element("div", {'id':'netusermessage','class':'toolbitem','style':'float:left;clear:both;font-weight:bold'});

	graphDiv.style.width = width+"px";
	graphDiv.style.height = height+"px";

	var outerDiv = new Element('div', {'id':'graphUserDiv-outer', 'style': 'border:1px solid gray;clear:both;float:left;overflow:hidden;'});
	outerDiv.insert(messagearea);
	outerDiv.insert(graphDiv);
	$("social-embed-div").insert(outerDiv);

	forcedirectedGraph = createNewEmbedForceDirectedGraphSocial('graphUserDiv', "");

	// THE KEY
	var keybar = createSocialNetworkGraphKey();
	// THE TOOLBAR
	var toolbar = createEmbedSocialGraphToolbar(forcedirectedGraph, "social-embed-div");

	$("social-embed-div").insert({top: toolbar});
	$("social-embed-div").insert({top: keybar});

	var parentwidth = framewidth-30;
	var parentheight = frameheight-150;
	if ($("visheader")) {
		var headerheight = $("visheader").offsetHeight;
		parentheight = parentheight - headerheight;
	}

	outerDiv.style.width = parentwidth+"px";
	outerDiv.style.height = parentheight+"px";
	$("social-embed-div").style.width = parentwidth+"px";
	$("social-embed-div").style.height = parentheight+"px";



	loadSocialData(forcedirectedGraph, toolbar, messagearea);
}

function loadSocialData(forcedirectedGraph, toolbar, messagearea) {

	messagearea.update(getLoading("<?php echo $LNG->NETWORKMAPS_SOCIAL_LOADING_MESSAGE; ?>"));

	var userdata = NODE_ARGS['userdata'];
	//alert(userdata.toSource());

	//NODE_ARGS now encoded on the way in
	var reqUrl = SERVICE_ROOT+"&method=getconnectionsfromjsonld&url="+NODE_ARGS['url']+
				"&withposts="+NODE_ARGS['withposts']+"&timeout="+NODE_ARGS['timeout'];

	//alert(reqUrl);

	new Ajax.Request(reqUrl, { method:'post',
  			onSuccess: function(transport){
  				//alert(transport.responseText);

  				var json = null;
  				try {
  					json = transport.responseText.evalJSON();
  				} catch(e) {
  					alert(e);
  				}
      			if(json.error){
      				alert(json.error[0].message);
      				return;
      			}

      			var conns = json.connectionset[0].connections;
      			//alert("connection count = "+conns.length);
      			if (conns.length > 0) {
      				var connectionadded = false;
	      			for(var i=0; i< conns.length; i++){
	      				var c = conns[i].connection;

						// Unobfuscate if data available.
						if (userdata && userdata != "" && c && c.from && c.to) {
							var fN = c.from[0].cnode;
							var tN = c.to[0].cnode;
							var fromuser = fN.users[0].user;
							fromuser = unobfuscateUser(fromuser);
							var touser = tN.users[0].user;
							touser = unobfuscateUser(touser);
	      				}

						if (addConnectionToFDGraphSocial(c, forcedirectedGraph)) {
							connectionadded = true;
						}
	      			}

					if (connectionadded) {
						if (!forcedirectedGraph.root) {
							computeMostConnectedNode(forcedirectedGraph);
						}
						layoutAndAnimateSocial(forcedirectedGraph, messagearea);
						toolbar.style.display = 'block';
					} else {
						messagearea.innerHTML="<?php echo $LNG->NETWORKMAPS_NO_RESULTS_MESSAGE; ?>";
						toolbar.style.display = 'none';
					}
				} else {
					messagearea.innerHTML="<?php echo $LNG->NETWORKMAPS_NO_RESULTS_MESSAGE; ?>";
					toolbar.style.display = 'none';
				}
      		}
      	});
}

loadEmbedSocialNet();