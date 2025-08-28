-- StartServer.lua
-- This script starts a server and sends HTTP notifications for creation and deletion,
-- allowing clients to connect to it.

-- Modified from the original script: https://archive.org/download/roblox-clients-2006-2021/

-- Hosted on blue16.site. Please credit if you share. :3
-- Enjoy your blue16 server for free! :D

-- Services
local NetworkServer = game:service("NetworkServer")
local HttpService = game:GetService("HttpService")
local RunService = game:service("RunService")
local StarterGui = game:GetService("StarterGui")

-- HTTP POST function with error handling
local function http_post(url, data)
    local json = HttpService:JSONEncode(data or {})
    local success, response = pcall(function()
        return HttpService:PostAsync(url, json, Enum.HttpContentType.ApplicationJson, false)
    end)
    if success then
        return response
    else
        warn("[HTTP POST] Error sending data to " .. url .. ": " .. tostring(response))
        return nil
    end
end

-- Send create notification
local createResponse = http_post("https://blue16.site/api/createserver.php", {port = 53640})
if createResponse then
    print("[Server] Created: " .. createResponse)
else
    warn("[Server] Create request failed.")
end

-- Start the server
NetworkServer:start(53640)

-- Graceful server shutdown with retry mechanism
local function onClose()
    local retries = 3
    local success, deleteResponse
    while retries > 0 do
        deleteResponse = http_post("https://blue16.site/api/deleteserver.php", {port = 53640})
        if deleteResponse then
            print("[Server] Deleted: " .. deleteResponse)
            return
        else
            warn("[Server] Delete request failed. Retrying...")
            retries = retries - 1
            wait(2)  -- Wait before retrying
        end
    end
    warn("[Server] Delete request failed after multiple attempts.")
end

-- Bind the onClose function to the game close event
game:BindToClose(onClose)

-- Start the game loop
RunService:run()
