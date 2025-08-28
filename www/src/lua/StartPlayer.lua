
-- StartPlayer.lua
-- this script connects a player to a server and sends http notifications for
-- join/leave events.

-- it is a modified version of the original StartServer.lua script from
-- https://archive.org/download/roblox-clients-2006-2021/

-- you are using this script for free and hosting on blue16.site.
-- so please do not remove these comments. credit is appreciated ;3
-- enjoy ur blue16 server!

local Visit = game:service("Visit")
local Players = game:service("Players")
local NetworkClient = game:service("NetworkClient")
local HttpService = game:GetService("HttpService")

function http_post(url, data)
    local json = HttpService:JSONEncode(data or {})
    local response = HttpService:PostAsync(url, json, Enum.HttpContentType.ApplicationJson, false)
    return response
end

local function onConnectionRejected()
	game:SetMessage("This game is not available. Please try another")
end

local function onConnectionFailed(_, id, reason)
	game:SetMessage("Failed to connect to the Game. (ID=" .. id .. ", " .. reason .. ")")
end

local function notifyAddPlayer()
	local player = Players.LocalPlayer
	local username = player and player.Name or "Unknown"
	local ok, res = pcall(function()
		return http_post("http://127.0.0.1:5500/www/api/addplayer.php", {username = username})
	end)
	if ok then
		print("[Player] Joined: ", res)
	else
		warn("[Player] Add request failed:", res)
	end
end

local function notifyRemovePlayer()
	local player = Players.LocalPlayer
	local username = player and player.Name or "Unknown"
	local ok, res = pcall(function()
		return http_post("http://127.0.0.1:5500/www/api/removeplayer.php", {username = username})
	end)
	if ok then
		print("[Player] Left: ", res)
	else
		warn("[Player] Remove request failed:", res)
	end
end

local function onConnectionAccepted(peer, replicator)
	local worldReceiver = replicator:SendMarker()
	local received = false
	
	local function onWorldReceived()
		received = true
	end
	
	worldReceiver.Received:connect(onWorldReceived)
	game:SetMessageBrickCount()
	
	while not received do
		workspace:ZoomToExtents()
		wait(0.5)
	end
	
	local player = Players.LocalPlayer
	game:SetMessage("Requesting character")
	
	replicator:RequestCharacter()
	game:SetMessage("Waiting for character")
	
	while not player.Character do
		player.Changed:wait()
	end
	
	game:ClearMessage()
	notifyAddPlayer()
end


NetworkClient.ConnectionAccepted:connect(onConnectionAccepted)
NetworkClient.ConnectionRejected:connect(onConnectionRejected)
NetworkClient.ConnectionFailed:connect(onConnectionFailed)

-- Notify on player leave
game:BindToClose(notifyRemovePlayer)

game:SetMessage("Connecting to Server")

local success, errorMsg = pcall(function ()
	local player = Players.LocalPlayer
	
	if not player then
		player = Players:createLocalPlayer(0)
	end
	
	NetworkClient:connect("localhost", 53640, 0)
end)

if not success then
	game:SetMessage(errorMsg)
end
