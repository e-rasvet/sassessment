import speech_recognition as sr
import sys
from pydub import AudioSegment


sound = AudioSegment.from_wav(sys.argv[1])
sound = sound.set_channels(1)
sound.export("/var/www/html/moodle/_py/testmono.wav", format="wav")

r = sr.Recognizer()
with sr.WavFile("/var/www/html/moodle/_py/testmono.wav") as source:              # use "test.wav" as the audio source
    audio = r.record(source)                        # extract audio data from the file

try:
    print(r.recognize(audio))         # recognize speech using Google Speech Recognition
except KeyError:                                    # the API key didn't work
    print("Error: Invalid API key or quota maxed out")
except LookupError:                                 # speech is unintelligible
    print("Error: Could not understand audio")
