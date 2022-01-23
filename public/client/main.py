import requests, time, os, json

from functions import loadSensors, saveTemperatures

uniqueHash = None
writeUrl = None
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

    saveTemperatures(sensors, writeUrl, uniqueHash)


    #cmdipa = os.popen('ip a | grep link/ether').read()
    #cmdipa = cmdipa.splitlines()
    #allmacaddress = []
    #for mac in cmdipa:
    #    mac = mac.split(' ')
    #    allmacaddress.append(mac[5])

except FileNotFoundError as exception:
    print("Config file not found: " + str(exception))
