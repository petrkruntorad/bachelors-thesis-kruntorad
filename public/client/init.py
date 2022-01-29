import os, json

try:
    configFile = open('config.json')
    sensors = []
    writeInterval = None

    configData = json.load(configFile)

    if 'writeInterval' in configData.keys():
        writeInterval = configData['writeInterval']
    else:
        raise ValueError("Write interval is missing in config file.")

except FileNotFoundError as exception:
    print("Config file not found: " + str(exception))
