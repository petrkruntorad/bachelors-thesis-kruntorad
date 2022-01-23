import requests, time, os, json

try:
    configFile = open('config.json')
    sensors = []
    writeInterval = None

    configData = json.load(configFile)

    if 'writeInterval' in configData.keys():
        writeInterval = configData['writeInterval']
    else:
        raise ValueError("Write interval is missing in config file.")


    #cmdipa = os.popen('ip a | grep link/ether').read()
    #cmdipa = cmdipa.splitlines()
    #allmacaddress = []
    #for mac in cmdipa:
    #    mac = mac.split(' ')
    #    allmacaddress.append(mac[5])

except FileNotFoundError as exception:
    print("Config file not found: " + str(exception))
