import requests, time, os, json, logging
from functions import loadSensors, saveTemperatures, touchServer, updateConfig
logging.basicConfig(filename='log.txt', encoding='utf-8', level=logging.ERROR)

uniqueHash = None
writeUrl = None
touchUrl = None
updateUrl = None
sensors = loadSensors()

try:
    configFile = open('config.json')

    configData = json.load(configFile)

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

    touchServer(uniqueHash, touchUrl)

    updateConfig(uniqueHash, updateUrl, configData)

    saveTemperatures(sensors, writeUrl, uniqueHash)


    #cmdipa = os.popen('ip a | grep link/ether').read()
    #cmdipa = cmdipa.splitlines()
    #allmacaddress = []
    #for mac in cmdipa:
    #    mac = mac.split(' ')
    #    allmacaddress.append(mac[5])

except FileNotFoundError as exception:
    logging.error("Internal error: " + str(exception))
