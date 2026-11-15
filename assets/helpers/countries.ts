export interface CountryInfo {
  code: string
  dial: string
}

const RAW_COUNTRIES =
  'US:1,CA:1,RU:7,KZ:7,GB:44,DE:49,FR:33,IT:39,ES:34,CZ:420,SK:421,PL:48,UA:380,BY:375,AT:43,CH:41,NL:31,BE:32,' +
  'LU:352,IE:353,PT:351,DK:45,SE:46,NO:47,FI:358,IS:354,EE:372,LV:371,LT:370,HU:36,RO:40,BG:359,GR:30,HR:385,' +
  'SI:386,RS:381,BA:387,ME:382,MK:389,AL:355,XK:383,MD:373,GE:995,AM:374,AZ:994,TR:90,CY:357,MT:356,LI:423,' +
  'MC:377,SM:378,VA:379,AD:376,GI:350,FO:298,GL:299,IL:972,PS:970,JO:962,LB:961,SY:963,IQ:964,IR:98,KW:965,' +
  'SA:966,YE:967,OM:968,AE:971,QA:974,BH:973,EG:20,LY:218,TN:216,DZ:213,MA:212,SD:249,SS:211,ET:251,ER:291,' +
  'DJ:253,SO:252,KE:254,UG:256,TZ:255,RW:250,BI:257,CD:243,CG:242,GA:241,GQ:240,CM:237,NG:234,GH:233,CI:225,' +
  'SN:221,ML:223,BF:226,NE:227,TG:228,BJ:229,LR:231,SL:232,GN:224,GW:245,GM:220,MR:222,CV:238,ST:239,TD:235,' +
  'CF:236,AO:244,ZM:260,ZW:263,MW:265,MZ:258,NA:264,BW:267,ZA:27,LS:266,SZ:268,MG:261,MU:230,SC:248,KM:269,' +
  'IN:91,PK:92,BD:880,LK:94,NP:977,BT:975,MV:960,AF:93,UZ:998,TM:993,TJ:992,KG:996,MN:976,CN:86,HK:852,MO:853,' +
  'TW:886,JP:81,KR:82,KP:850,VN:84,TH:66,LA:856,KH:855,MM:95,MY:60,SG:65,ID:62,PH:63,BN:673,TL:670,AU:61,' +
  'NZ:64,PG:675,FJ:679,SB:677,VU:678,WS:685,TO:676,KI:686,TV:688,NR:674,PW:680,FM:691,MH:692,CK:682,NU:683,' +
  'NC:687,PF:689,MX:52,GT:502,BZ:501,SV:503,HN:504,NI:505,CR:506,PA:507,CU:53,HT:509,JM:1876,CO:57,VE:58,' +
  'EC:593,PE:51,BO:591,CL:56,AR:54,UY:598,PY:595,BR:55,GY:592,SR:597,FK:500,AW:297,CW:599'

export const COUNTRIES: CountryInfo[] = RAW_COUNTRIES.split(',').map((item: string) => {
  const parts = item.split(':')

  return { code: parts[0], dial: parts[1] }
})

const dialToCode = new Map<string, string>()
for (const country of COUNTRIES) {
  if (!dialToCode.has(country.dial)) {
    dialToCode.set(country.dial, country.code)
  }
}

const dialByCode = new Map<string, string>(
  COUNTRIES.map((country: CountryInfo): [string, string] => {
    return [country.code, country.dial]
  }),
)

export function flagEmoji(code: string): string {
  if (!/^[A-Za-z]{2}$/.test(code)) {
    return ''
  }

  const points = code
    .toUpperCase()
    .split('')
    .map((char: string) => {
      return 127397 + char.charCodeAt(0)
    })

  return String.fromCodePoint(...points)
}

export function getDialCode(code: string): string {
  return dialByCode.get(code) ?? ''
}

export function getCountryName(code: string, locale: string): string {
  try {
    const names = new Intl.DisplayNames([locale], { type: 'region' })

    return names.of(code) ?? code
  } catch {
    return code
  }
}

export function getEnglishCountryName(code: string): string {
  return getCountryName(code, 'en')
}

export interface PhoneParts {
  code: string
  number: string
}

export function splitPhone(value: string): PhoneParts {
  const trimmed = value.trim()
  const hasPlus = trimmed.startsWith('+') || trimmed.startsWith('00')
  if (!hasPlus) {
    return { code: '', number: trimmed }
  }

  const withoutPrefix = trimmed.startsWith('+') ? trimmed.slice(1) : trimmed.slice(2)
  const digits = withoutPrefix.replace(/\D/g, '')

  for (let length = 4; length >= 1; length--) {
    const dial = digits.slice(0, length)
    const code = dialToCode.get(dial)
    if (!code || dial.length !== length) {
      continue
    }

    let consumed = 0
    let index = 0
    while (index < withoutPrefix.length && consumed < length) {
      if (/\d/.test(withoutPrefix.charAt(index))) {
        consumed++
      }

      index++
    }

    const number = withoutPrefix.slice(index).replace(/^[\s\-.()]+/, '')

    return { code, number }
  }

  return { code: '', number: trimmed }
}

export function buildPhone(code: string, number: string): string {
  const trimmed = number.trim()
  if (trimmed === '') {
    return ''
  }

  const dial = getDialCode(code)
  if (dial === '') {
    return trimmed
  }

  return `+${dial} ${trimmed}`
}
