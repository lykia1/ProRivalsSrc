"""Extract only numeric rule tables from the C++ source, never database credentials."""
import json,re,subprocess
from pathlib import Path
root=Path(__file__).resolve().parents[2]
text=(root/'Common/AtumSJ.cpp').read_bytes().decode('latin1')
def table(name,width):
    body=re.search(r'const\s+\w+\s+'+name+r'\[\]\s*=\s*\{(.*?)\n\};',text,re.S).group(1)
    body=re.sub(r'//[^\n]*|/\*.*?\*/','',body,flags=re.S)
    rows=[]
    for row in re.findall(r'\{([^{}]+)\}',body):
        vals=[float(x.strip().rstrip('fF')) for x in row.split(',') if x.strip()]
        if len(vals)!=width:raise ValueError((name,row))
        rows.append(vals)
    assert [int(x[0]) for x in rows]==list(range(len(rows))),name
    return rows
local=(root/'Common/LocalizationDefineCommon.h').read_bytes().decode('latin1')
parts=['ATTACK','DEFENSE','FUEL','SOUL','SHIELD','DODGE']
gears={g:[int(re.search(r'#define\s+STAT_'+g+'_'+p+r'_PART\s+(\d+)',local).group(1)) for p in parts] for g in ['BGEAR','MGEAR','AGEAR','IGEAR']}
data={'source':{'repository':'lykia1/ProRivalsSrc','commit':subprocess.check_output(['git','rev-parse','HEAD'],cwd=root,text=True).strip(),'profile':'S_CAN_SERVER_SETTING_HSSON','files':['Common/AtumSJ.cpp','Common/AtumSJ.h','Common/AtumParam.h','Common/LocalizationDefineCommon.h']},'gears':gears,'statFields':['index','attackRate','hitBonus','pierce','defenseRate','shield','dodgeRate','legacyHp','monsterPierce'],'stats':table('g_arrLevelStatValue',9),'experienceFields':['level','increment','total','deathLoss','deathSpi'],'experience':table('arrLevelExperienceNew',5),'unitKinds':{'BGEAR':1,'MGEAR':16,'AGEAR':256,'IGEAR':4096},'nations':{'BCU':2,'ANI':4},'cities':{'BCU':2001,'ANI':2002}}
assert len(data['stats'])==342
out=root/'web-port/data/rules.json';out.write_text(json.dumps(data,ensure_ascii=False,separators=(',',':'))+'\n')
print(f"Extracted {len(data['stats'])} stat rows, {len(data['experience'])} experience rows, 4 gear profiles")
