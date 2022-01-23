import os, time, requests

# variables
sensors = []
temperatures = []


def loadSensors():
    cmdLs = os.popen('ls sys/bus/w1/devices/ | grep ^28').read()
    cmdLs = cmdLs.splitlines()
    for id in cmdLs:
        sensors.append(id)
    return sensors


def logEvent(eventContent: str, eventType: str):
    print(eventContent)


def saveTemperatures(sensors: list, writeUrl: str, uniqueHash: str):
    for singleSensor in range(len(sensors)):
        for polltime in range(0, 3):
            text = ''
            while text.split("\n")[0].find("YES") == -1:
                tfile = open("/sys/bus/w1/devices/" + sensors[singleSensor] + "/w1_slave")
                text = tfile.read()
                tfile.close()
                time.sleep(1)
            secondline = text.split("\n")[1]
            temperatureData = secondline.split(" ")[9]
            temperature = float(temperatureData[2:]) / 1000

            session = requests.Session()

            post_data = {'sensorId': sensors[singleSensor], 'rawSensorData': temperature, 'uniqueHash': uniqueHash}

            session.post(url=writeUrl, data=post_data)
