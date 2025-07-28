-- StartServer.lua
-- this script starts a server and sends http notifications for creation and deletion,
-- allowing clients to connect to it

-- this is also a modified version of the original StartServer.lua script!
-- it is available at
-- https://archive.org/download/roblox-clients-2006-2021/

-- youre also using this script for free and hosting on blue16.xyz,
-- so please do not remove these comments
-- please credit me if you got this code somehow! that would be nice :3
-- enjoy ur blue16 server for free id say!1!

local NetworkServer = game:service("NetworkServer")
local HttpService = game:GetService("HttpService")

function http_post(url, data)
    local json = HttpService:JSONEncode(data or {})
    local response = HttpService:PostAsync(url, json, Enum.HttpContentType.ApplicationJson, false)
    return response
end

-- Send create notification
local success, result = pcall(function()
    return http_post("https://blue16.xyz/api/createserver.php", {port = 53640})
end)
if success then
    print("[Server] Created: ", result)
else
    warn("[Server] Create request failed:", result)
end

NetworkServer:start(53640)

local RunService = game:service("RunService")

-- Hook server close to send delete notification
local function onClose()
    local ok, res = pcall(function()
        return http_post("https://blue16.xyz/api/deleteserver.php", {port = 53640})
    end)
    if ok then
        print("[Server] Deleted: ", res)
    else
        warn("[Server] Delete request failed:", res)
    end
end

game:BindToClose(onClose)

RunService:run()