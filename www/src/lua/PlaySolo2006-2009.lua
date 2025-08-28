-- PlaySolo2006-2009.lua
-- Enhances solo play in classic Roblox Studio (2006–2009)
-- Modified from the original script: https://archive.org/download/roblox-clients-2006-2021/
-- Hosted on blue16.site. Please credit if you share. :3

-- Services
local Players = game:GetService("Players")
local RunService = game:GetService("RunService")
local StarterGui = game:GetService("StarterGui")

-- Create or get the local player
local player = Players.LocalPlayer
if not player then
    player = Players:createLocalPlayer(0)
end

-- Wait for the player's character to load
local function waitForChild(parent, name)
    local child = parent:FindFirstChild(name)
    while not child do
        parent.ChildAdded:Wait()
        child = parent:FindFirstChild(name)
    end
    return child
end

-- Handle character respawn
local function onCharacterAdded(character)
    local humanoid = waitForChild(character, "Humanoid")
    humanoid.Died:Connect(function()
        print("You died! Respawning in 5 seconds...")
        wait(5)
        player:LoadCharacter()
    end)
end

-- Connect to the CharacterAdded event
player.CharacterAdded:Connect(onCharacterAdded)

-- Initial character load
player:LoadCharacter()

-- Start the game loop
RunService:Run()
