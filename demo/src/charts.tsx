import { Area, AreaChart, Cell, Pie, PieChart, ResponsiveContainer, Tooltip } from 'recharts';

export function ActivityChart({ data }: { data: { day: string; shipments: number }[] }) {
  return <ResponsiveContainer width="100%" height={230}>
    <AreaChart data={data}>
      <defs>
        <linearGradient id="fill" x1="0" y1="0" x2="0" y2="1">
          <stop offset="0%" stopColor="#e3222e" stopOpacity={.25}/>
          <stop offset="100%" stopColor="#e3222e" stopOpacity={0}/>
        </linearGradient>
      </defs>
      <Area type="monotone" dataKey="shipments" stroke="#e3222e" fill="url(#fill)" strokeWidth={2}/>
      <Tooltip/>
    </AreaChart>
  </ResponsiveContainer>;
}

export function StatusDonut({ data }: { data: { name: string; value: number; color: string }[] }) {
  return <ResponsiveContainer width="52%" height={150}>
    <PieChart>
      <Pie data={data} dataKey="value" innerRadius={45} outerRadius={66} paddingAngle={3}>
        {data.map(x => <Cell key={x.name} fill={x.color}/>) }
      </Pie>
    </PieChart>
  </ResponsiveContainer>;
}
