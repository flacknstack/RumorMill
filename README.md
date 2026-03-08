# Rumor Mill

This plugin allows users to spread rumors about others. You can choose whether to target an entire group or just specific characters.


``` ## Link
misc.php?action=gossip

## templates
- gossip
- gossip_bit
- gossip_edit
- gossip_form
- gossip_index
- gossip_modcp
- gossip_modcp_nav
- gossip_modcp_new

## variable
**header**
``{$newgossip_alert}``
<br />
**index**
``{$gossip_index}``
<br />
**modcp_nav_users**
`` {$gossip_nav}``

## CSS
**gossip.css**
```
.form_flex{ 
display: flex; 
flex-wrap: wrap; 
justify-content: center;
} 

.form_box{ 
margin: 5px;
}

.form_title{ 
background: #0066a2 url(images/thead.png) top left repeat-x; 
color: #ffffff; 
border-bottom: 1px solid #263c30; 
padding: 5px 10px; 
text-align: center; 
margin: 4px auto; 
font-weight: bold;
}

.form_go{ 
width: 100%; 
text-align: center;
}

/*Gossip*/

.gossip_flex{ 
display: flex; 
flex-wrap: wrap; 
align-items: center;
}

.gossip_box{ 
width: 33%; 
margin: 5px;
}

.gossip_rumour{ 
height: 100px; 
overflow: auto; 
box-sizing: border-box; 
padding: 3px;
}

.gossip_info{ 
font-size: 10px; 
text-align: center;
}

/*Index*/

.gossip_title{ 
background: #0066a2 url(../../../images/thead.png) top left repeat-x;
color: #ffffff;
border-bottom: 1px solid #263c30;
padding: 8px;
}

.gossip_link{ 
color: #333; 
text-align: center; 
font-weight: bold;
}

.gossip_link a{ 
font-weight: bold; 
color: #333;
}

/*modcp*/

.new_gossip{ 
margin: 10px; 
width: 98%;
}

.gossip_about{ 
text-align: center; 
font-size: 12px;
}

.gossip_victims{ 
font-size: 14px; 
text-align: center;
}


.gossip_gossipbox{ 
padding: 5px; 
box-sizing: border-box;
}

.gossip_from{ 
text-align: center; 
font-size: 10x;
}
.gossip_modcp_flex{ 
display: flex; 
flex-wrap: wrap; 
align-items: center; 
justify-content: center;
}

.gossip_option{ 
margin: 5px 10px; 
padding: 2px; 
text-align: center;
}
```
