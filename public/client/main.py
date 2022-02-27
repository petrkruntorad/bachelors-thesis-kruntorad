import json
import logging
from functions import loadSensors, saveTemperatures, touchServer, updateConfig

logging.basicConfig(filename='log.txt', encoding='utf-8', level=logging.ERROR)

#variables inits
uniqueHash = None
writeUrl = None
touchUrl = None
updateUrl = None
#loads sensors
sensors = loadSensors()

try:
    #prepares config
    configFile = open('config.json')
    #loads config from file
    configData = json.load(configFile)

    #if values exists sets them to variable
    if 'writeUrl' in configData.keys():
        writeUrl = configData['writeUrl']
    else:
        raise ValueError("Target URL is missing in config file.")
    if 'uniqueHash' in configData.keys():
        uniqueHash = configData['uniqueHash']
    else:
        raise ValueError("Unique hash is missing in config file.")
    if 'touchUrl' in configData.keys():
        touchUrl = configData['touchUrl']
    else:
        raise ValueError("Unique hash is missing in config file.")
    if 'updateUrl' in configData.keys():
        updateUrl = configData['updateUrl']
    else:
        raise ValueError("Unique hash is missing in config file.")

    #updates info about device on api
    touchServer(uniqueHash, touchUrl)
    #updates config
    updateConfig(uniqueHash, updateUrl, configData)
    #writes temperatures
    saveTemperatures(sensors, writeUrl, uniqueHash)
except FileNotFoundError as exception:
    logging.error("Internal error: " + str(exception))
