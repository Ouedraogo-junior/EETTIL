export function ComingSoon({ title, step }: { title: string; step: number }) {
  return (
    <>
      <div className="ph">
        <div>
          <h1>{title}</h1>
          <div className="sub">Module prévu à l'étape {step} du développement.</div>
        </div>
      </div>
      <div className="panel">
        <div className="empty">
          <b>Bientôt disponible</b>
          Cette page sera construite dans une prochaine étape.
        </div>
      </div>
    </>
  )
}